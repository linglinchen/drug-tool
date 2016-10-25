<?php

namespace App;

use DB;
use Log;

use App\AppModel;
use App\Atom;
use App\Comment;
use App\Molecule;
use App\Assignment;

class Report extends AppModel {
    public static $reportTypes = [
        'discontinued' => 'Discontinued Monographs',
        'statuses' => 'Status Breakdown',
        'edits' => 'Edits',
        'openAssignments' => 'Open Assignments',
        'brokenLinks' => 'Broken Links',
        'queries' => 'Queries'
    ];

	protected static $_stepSizeSeconds = [
		'day' => 24 * 60 * 60,
		'week' => 7 * 24 * 60 * 60
	];

	/**
	 * Get a list of discontinued monographs and a count of the total.
	 *
	 * @return mixed[]
	 */
	public static function discontinued() {
		return [
			'totalCount' => Atom::countMonographs(),
			'discontinued' => Atom::getDiscontinuedMonographs()
		];
	}

	/**
	 * Get a count of atoms in each status.
	 *
	 * @return integer[]
	 */
	public static function statuses() {
		$results = Atom::select('status_id', DB::raw('COUNT(status_id)'))
				->whereIn('id', function ($q) {
					Atom::buildLatestIDQuery(null, $q);
				})
				->groupBy('status_id')
				->get();

		$output = [];
		foreach($results as $row) {
			$output[$row['status_id']] = $row['count'];
		}

		return $output;
	}

	/**
	 * Get a count of how many atoms were edited per day by each user. Multiple edits to an atom on the same day
	 * count as one.
	 *
	 * @param string $stepSize How much time between steps?
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param ?string $startTime (optional) Start time of the graph
	 * @param ?string $endTime (optional) End time of the graph
	 *
	 * @return array
	 */
	public static function edits($stepSize, $timezoneOffset = 0, $startTime = null, $endTime = null) {
		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day

		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);
		$startTime = self::_snapTime($startTime, $timezoneOffset, $stepSize, false);
		$endTime = self::_snapTime($endTime, $timezoneOffset, $stepSize, true);

		$timezoneOffsetPart = $timezoneOffset ?
				' AT TIME ZONE INTERVAL \'' . (int)$timezoneOffset . ':00\'' :
				'';
		$datePart = 'DATE_TRUNC(\'' . $stepSize . '\', created_at' . $timezoneOffsetPart . ')';
		$query = Atom::select(
					'modified_by',
					DB::raw('EXTRACT(EPOCH FROM ' . $datePart . ') AS x'),
					DB::raw('COUNT(DISTINCT entity_id) AS y')
				);
		$query->whereIn('id', function ($q) {
			$subQuery = DB::table('atoms')
					->select('id', DB::raw('row_number() over (partition by xml order by id) as row_number'));

			$q->select('id')
					->from(DB::raw('(' . $subQuery->toSql() . ') AS sub'))
					->mergeBindings($subQuery);
		});
		if($startTime) {
			$query->where('created_at', '>', DB::raw('TO_TIMESTAMP(' . $startTime . ')'));
		}
		if($endTime) {
			$query->where('created_at', '<', DB::raw('TO_TIMESTAMP(' . ($endTime + $stepSize) . ')'));
		}
		$query->groupBy(
					'modified_by',
					DB::raw($datePart)
				)
				->orderBy(DB::raw($datePart));
		$results = $query->get();

		if(sizeof($results)) {
			$startTime = $startTime ? $startTime : (int)$results[0]->x;
			$endTime = $endTime ? $endTime : (int)$results[sizeof($results) - 1]->x;
			$blankSeries = self::_buildBlankTimeSeries($startTime, $endTime, self::$_stepSizeSeconds[$stepSize]);
		}

		$output = [];
		foreach($results as $row) {
			$userId = (int)$row['modified_by'];

			if(!isset($output[$userId])) {
				$output[$userId] = $blankSeries;
			}

			$output[$userId][(int)$row->x] = [
				'x' => (int)$row->x,
				'y' => (int)$row->y
			];
		}

		return $output;
	}

	/**
	 * Determine how many assignments were open during a time period.
	 *
	 * @param string $stepSize How much time between steps?
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param ?string $startTime (optional) Start time of the graph
	 * @param ?string $endTime (optional) End time of the graph
	 *
	 * @return array
	 */
	public static function openAssignments($stepSize, $timezoneOffset = 0, $startTime = null, $endTime = null) {
		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day
		$stepSizeSeconds = self::$_stepSizeSeconds[$stepSize];

		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);
		$startTime = self::_snapTime($startTime, $timezoneOffset, $stepSize, false);
		$endTime = self::_snapTime($endTime, $timezoneOffset, $stepSize, true);

		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day

		$timezoneOffsetPart = $timezoneOffset ?
				' AT TIME ZONE INTERVAL \'' . (int)$timezoneOffset . ':00\'' :
				'';
		$datePart = 'DATE_TRUNC(\'' . $stepSize . '\', created_at' . $timezoneOffsetPart . ')';
		$query = Assignment::select(
					'user_id',
					DB::raw('EXTRACT(EPOCH FROM DATE_TRUNC(\'' . $stepSize . '\', created_at' . $timezoneOffsetPart . ')) AS opened'),
					DB::raw('EXTRACT(EPOCH FROM DATE_TRUNC(\'' . $stepSize . '\', task_end' . $timezoneOffsetPart . ')) AS closed')
				)
				->whereNotNull('created_at');	 //filter out missing timestamps
		if($endTime) {
			$cutoff = $endTime + $stepSizeSeconds;
			$query->where('created_at', '<', DB::raw('TO_TIMESTAMP(' . $cutoff . ')'));
			$query->where(function ($q) use ($cutoff) {
				$q->whereNull('task_end')
						->orWhere('task_end', '<', DB::raw('TO_TIMESTAMP(' . $cutoff . ')'));
			});

			if($startTime) {
				$query->where(function ($q) use ($startTime) {
					$q->whereNull('task_end')
							->orWhere('task_end', '>=', DB::raw('TO_TIMESTAMP(' . $startTime . ')'));
				});
			}
		}
		$query->orderBy('created_at', 'ASC');

		$results = $query->get();
		if(sizeof($results)) {
			$startTime = $startTime ? $startTime : (int)$results[0]->opened;
			$endTime = $endTime ?
					$endTime :
					strtotime(date('Y-m-d 00:00:00 ' . ($timezoneOffset >= 0 ? '+' : '') . $timezoneOffset . ':00'));
			$blankSeries = self::_buildBlankTimeSeries($startTime, $endTime, $stepSizeSeconds);
		}

		$output = [];
		foreach($results as $row) {
			$userId = (int)$row['user_id'];
			if(!isset($output[$userId])) {
				$output[$userId] = $blankSeries;
			}

			//ensure that the bookends are within the specified time range
			$start = $row->opened < $startTime ? $startTime : (int)$row->opened;
			$end = $row->closed ? (int)$row->closed : $endTime;

			self::_applyAssignmentToSeries($output[$userId], $stepSizeSeconds, $start, $end);
		}

		return $output;
	}

	/**
	 * Generate a list of broken links, and get the total.
	 *
	 * @return array
	 */
	public static function links() {
		$brokenLinks = [];
		$total = 0;
		$atoms = self::_getKeyedAtoms();
		$molecules = self::_getKeyedMolecules();
		foreach($atoms as $atom) {
			$atom->xml = simplexml_load_string($atom->xml);
		}

		foreach($atoms as $atom) {
			$elements = $atom->xml->xpath('//see|include');
			$total += sizeof($elements);
			foreach($elements as $element) {
				$attributes = $element->attributes();
				$refid = isset($attributes['refid']) ? current($attributes['refid']) : null;
				$parsedRefid = preg_split('/[\/:]/', $refid);
				$valid = sizeof($parsedRefid) > 1;
				if($valid) {
					$type = $parsedRefid[0];
					$valid = false;
					if($type == 'a') {		//atom
						$valid = isset($atoms[$parsedRefid[1]]);
						if($valid) {
							$atom = $atoms[$parsedRefid[1]];
							if(isset($parsedRefid[2])) {		//deep link
								$valid = !!sizeof($atom->xml->xpath('//[id = "' . $parsedRefid[2] . '"]'));
							}
						}
					}
					else if($type == 'm') {	 //molecule
						$valid = isset($molecules[$parsedRefid[1]]);
					}
				}

				if(!$valid) {
					$linkRefid = 'a:' . $atom->entity_id;
					$closestAncestorId = self::_closestId($element->xpath('..')[0]);
					if($closestAncestorId) {
						$linkRefid .= '/' . $closestAncestorId;
					}

					$brokenLinks[] = [
						'type' => $element->getName(),
						'atomTitle' => $atom->title,
						'contents' => preg_replace('/(^<[^>]*>|<[^>]*>$)/', '', $element->asXML()),
						'linkRefid' => $linkRefid,
						'destRefid' => $refid
					];
				}
			}
		}

		return [
			'brokenLinks' => $brokenLinks,
			'total' => $total
		];
	}

	/**
	 * Generate a list of comment queries posted during a time period.
	 *
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param ?string $startTime (optional) Start time of the graph
	 * @param ?string $endTime (optional) End time of the graph
	 *
	 * @return array
	 */
	public static function queries($timezoneOffset = 0, $startTime = null, $endTime = null) {
		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);

		$atomSubQuery = Atom::select('entity_id', 'title', 'alpha_title')
				->whereIn('id', function ($q) {
					Atom::buildLatestIDQuery(null, $q);
				});

		$rawAtomSubQuery = DB::raw('(' . $atomSubQuery->toSql() . ') AS atom_subquery');

		$query = Comment::select(
					'comments.*',
					'atom_subquery.entity_id AS entity_id',
					'atom_subquery.title AS title',
					'atom_subquery.alpha_title AS atom_title',
					'users.firstname AS firstname',
					'users.lastname AS lastname'
				)
				->leftJoin('users', 'comments.user_id', '=', 'users.id')
				->leftJoin($rawAtomSubQuery, function ($join) {
					$join->on('comments.atom_entity_id', '=', 'atom_subquery.entity_id');
				});

		if($startTime) {
			$query->where('comments.created_at', '>', DB::raw('TO_TIMESTAMP(' . $startTime . ')'));
		}
		if($endTime) {
			$query->where('comments.created_at', '<', DB::raw('TO_TIMESTAMP(' . ($endTime + self::$_stepSizeSeconds['day']) . ')'));
		}

		$query->where('text', 'LIKE', '%</query>%');

		$query->orderBy('comments.id', 'DESC');

		return $query->get();
	}

	/**
	 * Snap the timestamp to an interval boundary.
	 * It is recommended to round up for an end time, and down for a start time.
	 *
	 * @param ?int $time The timestamp to operate on
	 * @param integer $timezoneOffset (optional) Timezone offset in hours
	 * @param string $interval A key from self::$_stepSizeSeconds
	 * @param boolean $roundUp Round up or down?
	 *
	 * @return ?int
	 */
	protected static function _snapTime($time, $timezoneOffset, $interval, $roundUp) {
		$output = null;
		if($time === null) {
			return $output;
		}

		$timezoneOffsetSeconds = $timezoneOffset * 60 * 60;
		$time += $timezoneOffsetSeconds;
		$output = strtotime(date('Y-m-d', $time)) - $timezoneOffsetSeconds;
		if($interval == 'day') {
			if($roundUp) {
				$output += self::$_stepSizeSeconds['day'];
			}
		}
		else if($interval == 'week') {
			$day = date('N', $output) - 1;
			if($roundUp) {
				$output += self::$_stepSizeSeconds['day'] * (7 - $day);
			}
			else {
				$output -= self::$_stepSizeSeconds['day'] * $day;
			}
		}

		return $output;
	}

	/**
	 * Find the ID of an element or its closest ancestor.
	 *
	 * @param object $element The element to operate on
	 *
	 * @return ?string The ID or null if none is found in the element's lineage
	 */
	protected static function _closestId($element) {
		$parent = $element->xpath('..');
		$parent = $parent ? $parent[0] : null;
		$attributes = $element->attributes();
		if(isset($attributes['id'])) {
			return current($attributes['id']);
		}
		else if($parent) {
			return self::_closestId($parent);
		}
		else {
			return null;
		}
	}

	/**
	 * Get a list of all atoms keyed by their entity_id.
	 *
	 * @return object[]
	 */
	protected static function _getKeyedAtoms() {
		$results = Atom::select('entity_id', 'title', 'xml')
				->whereIn('id', function ($q) {
					Atom::buildLatestIDQuery(null, $q);
				})
				->get();

		$atoms = [];
		foreach($results as $atom) {
			$atoms[$atom->entity_id] = $atom;
		}

		return $atoms;
	}

	/**
	 * Get a list of all molecules keyed by their code.
	 *
	 * @return object[]
	 */
	protected static function _getKeyedMolecules() {
		$results = Molecule::all();

		$molecules = $results;
		foreach($results as $molecule) {
			$molecules[$molecule->code] = $molecule;
		}

		return $molecules;
	}

	/**
	 * Expand an assignment to cover a time range in a series.
	 *
	 * @param array $series The user's series
	 * @param string $stepSizeSeconds How much time between steps?
	 * @param integer $start The start of the assignment
	 * @param integer $end The end of the assignment
	 */
	protected static function _applyAssignmentToSeries(&$series, $stepSizeSeconds, $start, $end) {
		for($i = $start; $i <= $end; $i += $stepSizeSeconds) {
			if(!isset($series[$i])) {
				return;
			}
			++$series[$i]['y'];
		}
	}

	/**
	 * Make sure that the end time is >= the start time.
	 *
	 * @param integer $startTime Start time of the graph
	 * @param integer $endTime End time of the graph
	 *
	 * @return integer[] The re-ordered times
	 */
	protected static function _enforceRangeSanity($startTime, $endTime) {
		$times = [$startTime, $endTime];

		if($startTime && $endTime) {
			sort($times);
		}

		return $times;
	}

	/**
	 * Build a blank time series for the charting library to consume.
	 * Using this will ensure that time series in sparse data sets are always the same length.
	 *
	 * @param integer $startTime Start time of the series
	 * @param integer $endTime End time of the series
	 * @param integer $stepSize How much time between steps?
	 *
	 * @returns array
	 */
	protected static function _buildBlankTimeSeries($startTime, $endTime, $stepSize) {
		$blankSeries = [];
		for($i = $startTime; $i <= $endTime; $i += $stepSize) {
			$blankSeries[$i] = [
				'x' => $i,
				'y' => 0
			];
		}

		return $blankSeries;
	}
}