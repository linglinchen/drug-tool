<?php

namespace App;

use DB;
use Log;

use App\AppModel;
//use App\Product;
use App\Atom;
use App\Comment;
use App\Molecule;
use App\Assignment;


class Report extends AppModel {
	// $reportTypes left here to support defunct listAction function. Remove after routing it on front and back.
	public static $reportTypes = [
		'discontinued' => 'Discontinued Monographs',
		'statuses' => 'Status Breakdown',
		'edits' => 'Edits',
		'openAssignments' => 'Open Assignments',
		'brokenLinks' => 'Broken Links',
		'comments' => 'Comments',
		'moleculeStats' => 'Chapter Stats',
		'domainStats' => 'Domain Stats',
		'reviewerStats' => 'Reviewer Process Stats'
	];


	/**
	 * Get a menu of reports per product.
	 *
     * @param integer $productId Limit to this product
	 *
	 * @return mixed[]
	 */

	public static function reportMenu($productId) {
		//check if product is either dictionary and if so give separate menus.
		//Otherwise, provide generic drug type menu
		if ($productId == 3 || $productId == 5){
				switch ($productId) {
				    case 3:
				        $reportTypes = [
							'statuses' => 'Status Breakdown',
							'edits' => 'Edits',
							'openAssignments' => 'Open Assignments',
/*							'brokenLinks' => 'Broken Links',*/
							'comments' => 'Comments',
							'moleculeStats' => 'Chapter Stats',
							'domainStats' => 'Domain Stats',
							'reviewerStats' => 'Reviewer Process Stats'
						];

						break;
				    case 5:
				        $reportTypes = [
							'statuses' => 'Status Breakdown',
							'openAssignments' => 'Open Assignments',
							'brokenLinks' => 'Broken Links',
							'comments' => 'Comments',
							'moleculeStats' => 'Chapter Stats',
							'domainStats' => 'Category Stats'
						];

						break;

				}



		} else {

				$reportTypes = [
					'discontinued' => 'Discontinued Monographs',
					'statuses' => 'Status Breakdown',
					'edits' => 'Edits',
					'openAssignments' => 'Open Assignments',
					'brokenLinks' => 'Broken Links',
					'comments' => 'Comments',
					'moleculeStats' => 'Chapter Stats',
					'domainStats' => 'Domain Stats'
			];
		}


		return $reportTypes;
	}


	protected static $_stepSizeSeconds = [
		'day' => 24 * 60 * 60,
		'week' => 7 * 24 * 60 * 60
	];

	/**
	 * Get a list of discontinued monographs and a count of the total.
	 *
     * @param integer $productId Limit to this product
	 *
	 * @return mixed[]
	 */
	public static function discontinued($productId) {
		return [
			'totalCount' => Atom::countMonographs($productId),
			'discontinued' => Atom::getDiscontinuedMonographs($productId)
		];
	}

	/**
	 * Get a count of atoms in each status.
	 *
     * @param integer $productId Limit to this product
	 *
	 * @return integer[]
	 */
	public static function statuses($productId) {
		$results = Atom::select('status_id', DB::raw('COUNT(status_id)'))
                ->where('product_id', '=', $productId)
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
     * @param integer $productId Limit to this product
	 * @param string $stepSize How much time between steps?
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param ?string $startTime (optional) Start time of the graph
	 * @param ?string $endTime (optional) End time of the graph
	 *
	 * @return array
	 */
	public static function edits($productId, $stepSize, $timezoneOffset = 0, $startTime = null, $endTime = null) {
		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day
		$stepSizeSeconds = self::$_stepSizeSeconds[$stepSize];

		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : time();
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);
		$startTime = self::_snapTime($startTime, $timezoneOffset, $stepSize, false);
		$endTime = self::_snapTime($endTime, $timezoneOffset, $stepSize, true);

		$query = Atom::select(
					'entity_id',
					'modified_by',
					'title',
					DB::raw('EXTRACT(EPOCH FROM "created_at") AS x')
				)
                ->where('product_id', '=', $productId)
				->whereIn('id', function ($q) {
					$subQuery = DB::table('atoms')
							->select('id', DB::raw('row_number() over (partition by "xml" order by "id") as "row_number"'));

					$q->select('id')
							->from(DB::raw('(' . $subQuery->toSql() . ') AS sub'))
							->mergeBindings($subQuery);
				});

		if($startTime) {
			$query->where('created_at', '>', DB::raw('TO_TIMESTAMP(' . $startTime . ')'));
		}
		if($endTime) {
			$query->where('created_at', '<', DB::raw('TO_TIMESTAMP(' . ($endTime + $stepSizeSeconds) . ')'));
		}

		$query->orderBy('x', 'ASC');

		$results = $query->get();
		if(sizeof($results)) {
			if(!$startTime) {
				$startTime = self::_snapTime($results[0]->x, $timezoneOffset, $stepSize, false);
			}
			if(!$endTime) {
				$endTime = self::_snapTime($results[sizeof($results) - 1]->x, $timezoneOffset, $stepSize, true);
			}

			$blankSeries = self::_buildBlankTimeSeries($startTime, $endTime, self::$_stepSizeSeconds[$stepSize]);
			foreach($blankSeries as &$step) {
				$step['atoms'] = [];
			}
		}

		$output = [];
		foreach($results as $row) {
			$userId = (int)$row['modified_by'];

			if(!isset($output[$userId])) {
				$output[$userId] = $blankSeries;
			}

			$x = self::_snapTime($row->x, $timezoneOffset, $stepSize, false);
			++$output[$userId][$x]['y'];
			$output[$userId][$x]['atoms'][$row->entity_id] = $row->title;
		}

		return $output;
	}

	/**
	 * Determine how many assignments were open during a time period.
	 *
     * @param integer $productId Limit to this product
	 * @param string $stepSize How much time between steps?
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param ?string $startTime (optional) Start time of the graph
	 * @param ?string $endTime (optional) End time of the graph
	 *
	 * @return array
	 */
	public static function openAssignments($productId, $stepSize, $timezoneOffset = 0, $startTime = null, $endTime = null) {
		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day
		$stepSizeSeconds = self::$_stepSizeSeconds[$stepSize];

		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : time();
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);
		$startTime = self::_snapTime($startTime, $timezoneOffset, $stepSize, false);
		$endTime = self::_snapTime($endTime, $timezoneOffset, $stepSize, true);

		$stepSize = strtolower($stepSize);
		$stepSize = isset(self::$_stepSizeSeconds[$stepSize]) ? $stepSize : 'day';		//sanitize, and default to 1 day

		$query = Assignment::select(
					'user_id',
					DB::raw('EXTRACT(EPOCH FROM "assignments"."created_at") AS start'),
					DB::raw('EXTRACT(EPOCH FROM "task_end") AS end')
				)
				->join('atoms', 'assignments.atom_entity_id', '=', 'atoms.entity_id')
                ->where('product_id', '=', $productId)
				->whereNotNull('assignments.created_at');	 //filter out missing timestamps

		if($endTime) {
			$query->where('assignments.created_at', '<', DB::raw('TO_TIMESTAMP(' . $endTime . ')'));
		}
		if($startTime) {
			$query->where(function ($q) use ($startTime) {
				$q->whereNull('task_end')
						->orWhere('task_end', '>=', DB::raw('TO_TIMESTAMP(' . $startTime . ')'));
			});
		}

		$query->orderBy('assignments.created_at', 'ASC');

		$results = $query->get();
		if(sizeof($results)) {
			if(!$startTime) {
				$startTime = $results[0]->start ? $results[0]->start : $startTime;
				$startTime = self::_snapTime($startTime, $timezoneOffset, $stepSize, false);
			}

			$blankSeries = self::_buildBlankTimeSeries($startTime, $endTime, $stepSizeSeconds);
		}

		$output = [];
		foreach($results as $row) {
			$userId = (int)$row['user_id'];
			if(!isset($output[$userId])) {
				$output[$userId] = $blankSeries;
			}

			$start = $row['start'];
			$end = $row['end'] ? $row['end'] : $endTime;

			self::_applyAssignmentToSeries($output[$userId], $timezoneOffset, $stepSizeSeconds, $start, $end);
		}

		return $output;
	}



	/**
	 * Generate a list of broken links, and get the total.
	 *
     * @param integer $productId Limit to this product
	 *
	 * @return array
	 */
	public static function links($productId) {
		$brokenLinks = [];
		$total = 0;
		$atoms = self::_getKeyedAtoms($productId);
		$molecules = self::_getKeyedMolecules($productId);
		foreach($atoms as $atom) {
			$atom->xml = simplexml_load_string($atom->xml);

		}

		foreach($atoms as $atom) {
			$elements = $atom->xml->xpath('//xref|//see|include');
			$total += sizeof($elements);
			foreach($elements as $element) {
				$attributes = $element->attributes();
				$refid = isset($attributes['refid']) ? current($attributes['refid']) : null;
				$refid = preg_replace('/\#.*$/', '', $refid);
				$parsedRefid = preg_split('/[\/:]/', $refid);
				$valid = sizeof($parsedRefid) > 1;
				if($valid) {
					$type = $parsedRefid[0];
						$strippedParsedid=preg_replace('/#.*$/', '', $parsedRefid[1]);
					$valid = false;
					if($type == 'a') {		//atom
						$valid = isset($atoms[$strippedParsedid]);
						if($valid) {
							$atom = $atoms[$strippedParsedid];
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
	 * Generate a list of comment containing queries posted during a time period.
	 *
	 * @param integer $productId Limit to this product
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param ?string $startTime (optional) Start time of the graph
	 * @param ?string $endTime (optional) End time of the graph
	 * @param bool $queriesOnly (optional) Only show queries
	 * @param ?string $queryType (optional) Filter down to this type of query
	 *
	 * @return array
	 */
	public static function comments($productId, $timezoneOffset = 0, $startTime = null, $endTime = null, $queriesOnly = false, $queryType = null) {
		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);

		$atomSubQuery = Atom::select('entity_id', 'title', 'alpha_title')
                ->where('product_id', '=', DB::raw((int)$productId))		//laravel doesn't like bindings in subqueries
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

		if($queriesOnly) {
			if($queryType) {
				$queryType = self::_sanitizeQueryType($queryType);
				$queryMatcher = '%<query type="' . $queryType . '">%';
			}
			else {
				$queryMatcher = '%</query>%';
			}

			$query->where('text', 'LIKE', $queryMatcher);
		}

		$query->orderBy('comments.id', 'DESC');

		return $query->get();
	}

	/**
	 * Builds a CSV from a list of queries.
	 *
	 * @param object[] $comments
	 * @param ?string $queryType (optional) Filter down to this type of query
	 *
	 * @return Response
	 */
	public static function buildQueriesCSV($comments, $queryType = null) {
		$headings = ['atom_title', 'query_type', 'text', 'firstname', 'lastname', 'created_at'];

		$queries = self::_extractQueries($comments, $queryType);

		return self::arrayToCsv('queries.csv', $headings, $queries);
	}

	/**
	 * Provide an array of statistics about the molecules.
	 *
	 * @param integer $productId Limit to this product
	 *
	 * @return array
	 */
	public static function moleculeStats($productId) {
		$moleculeStats = self::_countCharsPerMolecule($productId);

		$stats = [
			'pageStats' => [		//these magic numbers need to be moved out into the products table when it exists
				'charsMean' => 2826,
				'charsStdErr' => 445
			],
			'molecules' => $moleculeStats
		];

		return $stats;
	}

	/**
	 * Provide an array of statistics about the domains.
	 *
	 * @param integer $productId Limit to this product
	 *
	 * @return array
	 */
	public static function domainStats($productId) {
		$domainStats = self::_countPerDomain($productId);

		$stats = [
			'domains' => $domainStats
		];

		return $stats;
	}

	/**
	 * Provide an array of statistics about the reviewer process.
	 *
	 * @param integer $productId Limit to this product
	 *
	 * @return array
	 */
	public static function reviewerStats($productId) {
		$reviewerStats = self::_countPerReviewer($productId);

		$stats = [
			'reviewer' => $reviewerStats
		];

		return $stats;
	}


	/**
	 * Extracts queries from a list of comments.
	 *
	 * @param object[] $comments
	 * @param ?string $queryType (optional) Filter down to this type of query
	 *
	 * @return array
	 */
	protected static function _extractQueries($comments, $queryType = null) {
		$rows = [];

		$queryType = self::_sanitizeQueryType($queryType);
		$querymatcher = '/<query' . ($queryType ? ' type="' . $queryType . '">' : '[^>]*>') . '(.*?)<\/query>/Ssi';
		$queryTypeMatcher = '/^<query type="([^"]*)/Si';
		$commentsArray = $comments->toArray();
		foreach($commentsArray as $comment) {
			preg_match_all($querymatcher, $comment['text'], $matches);
			$queries = [];
			foreach($matches[1] as $key => $text) {
				if(preg_match($queryTypeMatcher, $matches[0][$key], $queryTypeMatches)) {
					$queryType = $queryTypeMatches[1];
				}
				else {
					$queryType = null;
				}

				$row = $comment;
				$row['query_type'] = $queryType;
				$row['text'] = trim($text);
				$rows[] = $row;
			}
		}

		return $rows;
	}

	/**
	 * Remove dangerous characters from a query type string.
	 *
	 * @param ?string $queryType
	 *
	 * @return ?string
	 */
	protected static function _sanitizeQueryType($queryType) {
		return $queryType ? preg_replace('/[^a-z0-9\-\.]/i', '', $queryType) : $queryType;
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
	 * @param integer $productId Limit to this product
	 *
	 * @return object[]
	 */
	protected static function _getKeyedAtoms($productId) {
		$results = Atom::select('entity_id', 'title', 'xml')
                ->where('product_id', '=', $productId)
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
	 * @param integer $productId Limit to this product
	 *
	 * @return object[]
	 */
	protected static function _getKeyedMolecules($productId) {
		$results = Molecule::where('product_id', '=', $productId);

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
	 * @param ?integer $timezoneOffset (optional) Timezone offset in hours
	 * @param string $stepSizeSeconds How much time between steps?
	 * @param integer $start The start of the assignment
	 * @param integer $end The end of the assignment
	 */
	protected static function _applyAssignmentToSeries(&$series, $timezoneOffset, $stepSizeSeconds, $start, $end) {
		reset($series);
		$seriesStart = key($series);
		$start = $seriesStart > $start ? $seriesStart : self::_snapTime($start, $timezoneOffset, $stepSizeSeconds, false);
		$end = self::_snapTime($end, $timezoneOffset, $stepSizeSeconds, true);

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
		for($i = $startTime; $i < $endTime; $i += $stepSize) {
			$blankSeries[$i] = [
				'x' => $i,
				'y' => 0
			];
		}

		return $blankSeries;
	}

    /**
     * Estimate the number of printable characters in each chapter.
	 *
	 * @param integer $productId Limit to this product
     *
     * @return integer[]
     */
    protected static function _countCharsPerMolecule($productId) {
        $latestIds = Atom::select()
				->where('product_id', '=', $productId)
                ->whereNotNull('molecule_code')
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                });

        $wordsQuery = DB::table(DB::raw('(' . $latestIds->toSql() . ') AS latestIds'))
                ->select(
                    'molecule_code AS code',
                    DB::raw("char_length(trim(regexp_replace(regexp_replace(xml, '<[^>]*>', '', 'g'), '[\\r\\n\\t ]+', ' ', 'g'))) as char_count")
                )
                ->mergeBindings($latestIds->getQuery());

        $countQuery = DB::table(DB::raw('(' . $wordsQuery->toSql() . ') AS wordsQuery'))
                ->select('code', DB::raw('sum(char_count) AS chars'))
                ->mergeBindings($wordsQuery)
                ->groupBy('code');

        $counts = $countQuery->get();

        $stats = [];
        $molecules = Molecule::where('product_id', '=', $productId)
				->orderBy('sort', 'ASC')
				->get();
        foreach($molecules as $molecule) {
            $stats[$molecule['code']] = [
            	'code' => $molecule['code'],
            	'chars' => 0
            ];
        }

        foreach($counts as $row) {
            $stats[$row->code]['chars'] = $row->chars;
        }

        return $stats;
    }

	/**
     * Calculate process for each domain.
	 *
	 * @param integer $productId Limit to this product
     *
     * @return integer[]
     */
    protected static function _countPerDomain($productId) {
		$totalNumSum = 0;
		$reviewedSum = 0;
		$withCommentSum = 0;
		$woCommentSum = 0;

		//# of terms in each domain
		$totalNumSql = 'SELECT domain_code, count(id) FROM atoms
			WHERE id IN (SELECT MAX(id) FROM atoms WHERE product_id = '.$productId.' AND molecule_code IS NOT NULL GROUP BY entity_id)
			GROUP BY domain_code
			ORDER BY domain_code';

        $totalNumQuery = DB::select($totalNumSql);
        $totalNumArray = json_decode(json_encode($totalNumQuery), true);
		$stats['sum'] = [
			'totalNumSum' => 0,
			'reviewedSum' => 0,
			'withCommentSum' => 0,
			'woCommentSum' => 0
		];

        foreach ($totalNumArray as $totalNum){
			$stats[$totalNum['domain_code']] = [
				'name' => $totalNum['domain_code'],
				'totalNum' => $totalNum['count'],
				'reviewed' => 0,
				'perComplete' => 0,
				'withComment' => 0,
				'woComment' => 0
			];
			$totalNumSum += $totalNum['count'];
		}

		//# of terms that has been reviewed
		$reviewedNumSql = 'SELECT a.domain_code, count(a.id) FROM atoms a
			JOIN ASSIGNMENTS ass ON ass.atom_entity_id = a.entity_id
			WHERE a.id IN (SELECT MAX(id) FROM atoms WHERE product_id = '.$productId.' AND molecule_code IS NOT NULL GROUP BY entity_id)
				AND ass.id IN (
                SELECT MAX(assi.id) FROM assignments assi
                join tasks ta on ta.id = assi.task_id
                WHERE ta.title = \'Reviewer submits notes to Editor\'
					AND ta.product_id = '.$productId.'
                GROUP BY atom_entity_id
            )
				AND ass.task_end IS NOT NULL
			GROUP BY a.domain_code
			ORDER BY a.domain_code';

		$reviewedNumQuery = DB::select($reviewedNumSql);
		$reviewedNumArray = json_decode(json_encode($reviewedNumQuery), true);
		foreach ($reviewedNumArray as $reviewedNum){
			$stats[$reviewedNum['domain_code']]['reviewed'] = $reviewedNum['count'];
			$stats[$reviewedNum['domain_code']]['perComplete'] = number_format($reviewedNum['count']/$stats[$reviewedNum['domain_code']]['totalNum']*100, 2);
			$reviewedSum += $reviewedNum['count'];
		}

		//sent to editor with comments
		$withCommentNumSql = 'SELECT a.domain_code, count(com.id) FROM atoms a
			JOIN assignments ass ON ass.atom_entity_id = a.entity_id
			JOIN comments com on ass.user_id = com.user_id AND ass.atom_entity_id = com.atom_entity_id
			WHERE a.id IN (SELECT MAX(id) FROM atoms WHERE product_id = '.$productId.' AND molecule_code IS NOT NULL GROUP BY entity_id)
				AND ass.id IN (
                SELECT MAX(assi.id) FROM assignments assi
                join tasks ta on ta.id = assi.task_id
                WHERE ta.title = \'Reviewer submits notes to Editor\'
					AND ta.product_id = '.$productId.'
                GROUP BY atom_entity_id
            )
				AND ass.task_end IS NOT NULL
				AND com.id IN (SELECT MAX(id) FROM comments WHERE deleted_at IS NULL GROUP BY atom_entity_id, user_id)
			GROUP BY a.domain_code
			ORDER BY a.domain_code';

		$withCommentNumQuery = DB::select($withCommentNumSql);
		$withCommentNumArray = json_decode(json_encode($withCommentNumQuery), true);
		foreach ($withCommentNumArray as $withCommentNum){
			$stats[$withCommentNum['domain_code']]['withComment'] = $withCommentNum['count'];
			$woComment = $stats[$withCommentNum['domain_code']]['reviewed'] - $withCommentNum['count'];
			$stats[$withCommentNum['domain_code']]['woComment'] = $woComment;
			$withCommentSum += $withCommentNum['count'];
			$woCommentSum += $woComment;
		}

		$stats['sum'] = [
			'totalNumSum' => $totalNumSum,
			'reviewedSum' => $reviewedSum,
			'withCommentSum' => $withCommentSum,
			'woCommentSum' => $woCommentSum
		];

        return $stats;
    }

	/**
     * Calculate process for each reviewer.
	 *
	 * @param integer $productId Limit to this product
     *
     * @return integer[]
     */
    protected static function _countPerReviewer($productId) {
		$totalNumSum = 0;
		$reviewedSum = 0;
		$withCommentSum = 0;
		$woCommentSum = 0;

		//# of terms assigned to each reviewer
		$totalNumSql = 'SELECT us.firstname, us.lastname, COUNT(atom_entity_id) FROM assignments ass
			JOIN users us ON us.id = ass.user_id
            JOIN tasks t on t.id = ass.task_id
			WHERE us.id in (
				SELECT u.id FROM user_products up
				JOIN users u ON u.id = up.user_id
				JOIN groups g ON g.id = up.group_id
				WHERE g.title=\'Reviewer\' AND g.product_id = '.$productId.'
			)
			AND ass.id IN (
                SELECT MAX(assi.id) FROM assignments assi
                join tasks ta on ta.id = assi.task_id
                WHERE ta.title = \'Reviewer submits notes to Editor\'
					AND ta.product_id = '.$productId.'
                GROUP BY atom_entity_id
            )
			AND t.title = \'Reviewer submits notes to Editor\'
            AND t.product_id = '.$productId.'
			GROUP BY us.id';

        $totalNumQuery = DB::select($totalNumSql);
        $totalNumArray = json_decode(json_encode($totalNumQuery), true);
		$stats['sum'] = [
			'totalNumSum' => 0,
			'reviewedSum' => 0,
			'withCommentSum' => 0,
			'woCommentSum' => 0
		];

        foreach ($totalNumArray as $totalNum){
			$stats[$totalNum['lastname']] = [
				'name' => $totalNum['firstname'].' '.$totalNum['lastname'],
				'totalNum' => $totalNum['count'],
				'reviewed' => 0,
				'perComplete' => 0,
				'withComment' => 0,
				'woComment' => 0
			];
			$totalNumSum += $totalNum['count'];
		}

		//# of terms that has been reviewed
		$reviewedNumSql = 'SELECT us.firstname, us.lastname, COUNT(ass.atom_entity_id) FROM assignments ass
			JOIN users us ON us.id = ass.user_id
			JOIN tasks t on t.id = ass.task_id
			WHERE us.id in (
				SELECT u.id FROM user_products up
				JOIN users u ON u.id = up.user_id
				JOIN groups g ON g.id = up.group_id
				WHERE g.title=\'Reviewer\' AND g.product_id = '.$productId.'
			)
			AND ass.id IN (
                SELECT MAX(assi.id) FROM assignments assi
                join tasks ta on ta.id = assi.task_id
                WHERE ta.title = \'Reviewer submits notes to Editor\'
					AND ta.product_id = '.$productId.'
                GROUP BY atom_entity_id
            )
			AND t.title = \'Reviewer submits notes to Editor\'
            AND t.product_id = '.$productId.'
			AND task_end IS NOT NULL
			GROUP BY us.id';

		$reviewedNumQuery = DB::select($reviewedNumSql);
		$reviewedNumArray = json_decode(json_encode($reviewedNumQuery), true);
		foreach ($reviewedNumArray as $reviewedNum){
			$stats[$reviewedNum['lastname']]['reviewed'] = $reviewedNum['count'];
			$stats[$reviewedNum['lastname']]['perComplete'] = number_format($reviewedNum['count']/$stats[$reviewedNum['lastname']]['totalNum']*100, 2);
			$reviewedSum += $reviewedNum['count'];
		}

		 //sent to editor with comments
		 $withCommentNumSql = 'SELECT us.firstname, us.lastname, COUNT(ass.atom_entity_id) FROM assignments ass
			JOIN users us ON us.id = ass.user_id
			JOIN comments com on com.atom_entity_id = ass.atom_entity_id and com.user_id = ass.user_id
			JOIN tasks t on t.id = ass.task_id
			WHERE us.id in (
				SELECT u.id FROM user_products up
				JOIN users u ON u.id = up.user_id
				JOIN groups g ON g.id = up.group_id
				WHERE g.title=\'Reviewer\' AND g.product_id = '.$productId.'
    		)
			AND ass.id IN (
                SELECT MAX(assi.id) FROM assignments assi
                join tasks ta on ta.id = assi.task_id
                WHERE ta.title = \'Reviewer submits notes to Editor\'
					AND ta.product_id = '.$productId.'
                GROUP BY atom_entity_id
            )
			AND t.title = \'Reviewer submits notes to Editor\'
			AND t.product_id = '.$productId.'
			AND task_end IS NOT NULL
			AND com.id IN (SELECT MAX(id) FROM comments WHERE deleted_at IS NULL GROUP BY atom_entity_id, user_id)
			GROUP BY us.id';

		$withCommentNumQuery = DB::select($withCommentNumSql);
		$withCommentNumArray = json_decode(json_encode($withCommentNumQuery), true);
		foreach ($withCommentNumArray as $withCommentNum){
			$stats[$withCommentNum['lastname']]['withComment'] = $withCommentNum['count'];
			$woComment = $stats[$withCommentNum['lastname']]['reviewed'] - $withCommentNum['count'];
			$stats[$withCommentNum['lastname']]['woComment'] = $woComment;
			$withCommentSum += $withCommentNum['count'];
			$woCommentSum += $woComment;
		}

		$stats['sum'] = [
			'totalNumSum' => $totalNumSum,
			'reviewedSum' => $reviewedSum,
			'withCommentSum' => $withCommentSum,
			'woCommentSum' => $woCommentSum
		];

        return $stats;
    }
}