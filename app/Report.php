<?php

namespace App;

use DB;
use Log;

use App\AppModel;
use App\Atom;
use App\Assignment;

class Report extends AppModel {
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
	 * Get a count of how many atoms were edited per day by each user.
	 *
	 * @param string $stepSize How much time between steps?
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param ?string $startTime (optional) Start time of the graph
	 * @param ?string $endTime (optional) End time of the graph
	 *
	 * @return array
	 */
	public static function edits($stepSize, $timezoneOffset = 0, $startTime = null, $endTime = null) {
		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);

		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day

		$timezoneOffsetPart = $timezoneOffset ?
				' AT TIME ZONE INTERVAL \'' . (int)$timezoneOffset . ':00\'' :
				'';
		$datePart = 'DATE_TRUNC(\'' . $stepSize . '\', created_at' . $timezoneOffsetPart . ')';
		$query = Atom::select(
					'modified_by',
					DB::raw('EXTRACT(EPOCH FROM ' . $datePart . ') AS x'),
					DB::raw('COUNT(DISTINCT entity_id) AS y')
				);
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
		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);

		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day
		$stepSizeSeconds = self::$_stepSizeSeconds[$stepSize];

		$timezoneOffsetPart = $timezoneOffset ?
				' AT TIME ZONE INTERVAL \'' . (int)$timezoneOffset . ':00\'' :
				'';
		$datePart = 'DATE_TRUNC(\'' . $stepSize . '\', created_at' . $timezoneOffsetPart . ')';
		$query = Assignment::select(
					'user_id',
					DB::raw('EXTRACT(EPOCH FROM DATE_TRUNC(\'' . $stepSize . '\', created_at' . $timezoneOffsetPart . ')) AS opened'),
					DB::raw('EXTRACT(EPOCH FROM DATE_TRUNC(\'' . $stepSize . '\', task_end' . $timezoneOffsetPart . ')) AS closed')
				)
				->whereNotNull('created_at');		//filter out missing timestamps
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

			$start = $row->opened < $startTime ? $startTime : (int)$row->opened;
			$end = $row->closed ? (int)$row->closed : $endTime;
			self::_applyAssignmentToSeries($output[$userId], $stepSizeSeconds, $start, $end);
		}

		return $output;
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
	 * @return integer[]
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