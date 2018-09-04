<?php

namespace App;

use DB;
use Log;

use App\AppModel;
use App\Product;
use App\Atom;
use App\Comment;
use App\Molecule;
use App\Assignment;
use App\Status;


class Report extends AppModel {
	protected $dates = ['created_at', 'updated_at', 'deleted_at'];
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
		'reviewerStats' => 'Reviewer Process Stats',
		'suggestedImageStats' => 'Suggested Image Stats'
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
		$categoryType = Product::find($productId)->getCategorytype();
//Discontinued report is only for drugs since it is a drug content status (is the drug discontinued)/not a status of the MS record. Only include the discontinued report option for drug products.
		$drugProds = array(1,2,4);
		if (in_array($productId, $drugProds)) {
 			 $reportTypes['discontinued'] = 'Discontinued Monographs';
			}
		$reportTypes = [
			'statuses' => 'Status Breakdown',
			'edits' => 'Edits',
			'openAssignments' => 'Open Assignments',
			'brokenLinks' => 'Broken Links',
			'comments' => 'Comments',
			'moleculeStats' => 'Chapter Stats',
			'domainStats' => $categoryType ? $categoryType.' Stats' : ''
		];

		$dicProds = array(3,5);
		if ($productId == 3){
			$reportTypes['reviewerStats'] = 'Reviewer Process Stats';
		}
		if (in_array($productId, $dicProds)){
			$reportTypes['newFigures'] = 'New Figures (Implemented new this edition)';
			$reportTypes['suggestedImageStats'] = 'Suggested Image Report';
			$reportTypes['legacyImageStats'] = 'Legacy Image Report';
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
		ini_set('memory_limit', '1280M');
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
			//look for xref elements in dictionaries, and see in drugs
					if ($productId == 3 || $productId == 5){
						$elements = $atom->xml->xpath('//xref|include');
					} else {
						$elements = $atom->xml->xpath('//see|include');
					}
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
					$linkRefid = ' a:' . $atom->entity_id;
					$closestAncestorId = self::_closestId($element->xpath('..')[0]);
					if($closestAncestorId) {
						$linkRefid .= '/' . $closestAncestorId;
					}

					$brokenLinks[] = [
						'type' => $element->getName(),
						'atomTitle' => $atom->title,
						'contents' => preg_replace('/(^<[^>]*>|<[^>]*>$)/', '', $element->asXML()),
						'linkRefid' => trim($linkRefid),
						'destRefid' => trim($refid)
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
	 * Generate a list of new figures, and get the total.
	 *
     * @param integer $productId Limit to this product
	 *
	 * @return array
	 */
	public static function newFigures($productId, $timezoneOffset = 0, $startTime = null, $endTime = null, $queriesOnly = false, $queryType = null, $moleculeCode = 'Any', $domainCode = 'Any') {
		ini_set('memory_limit', '1280M');
		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);

		$newImplementedFigures = [];
				$total = 0;
		//use getRobustKeyedAtoms to get the data fields needed
		$atoms = self::_getRobustKeyedAtoms($productId);
		$molecules = self::_getKeyedMolecules($productId);
		foreach($atoms as $atom) {
			$atom->xml = simplexml_load_string($atom->xml);
		}

		foreach($atoms as $atom) {

			//look for implemented suggested figures components in current atom and save the snippet to $elements
//			$elements = $atom->xml->xpath('//component[@type="figure"]/file/@src[starts-with(., "suggested/")]');

			$elements = $atom->xml->xpath('//component[@type="figure"][file/@src[starts-with(., "suggested/")]]');

			$total += sizeof($elements);
			foreach($elements as $element) {

//					$sourceEntityId = ' a:' . $atom->entity_id;
					$sourceEntityId = $atom->entity_id;


					$newImplementedFigures[] = [
//						'currentAtomId' => $currentAtomId,
						'currentAtomId' => $atom->id,
						'type' => (string)$element[@type],
						'atomDomain' => $atom->domain_code,
						'atomMolecule' => $atom->molecule_code,
						'title' => $atom->title,
						'createdAt' => (string)$atom->created_at,
						'updatedAt' => (string)$atom->updated_at,
						'deletedAt' => (string)$atom->deleted_at,
						'modifiedByUser' => (string)$atom->modified_by,
						'filestub' => (string)$element->file[@src],
						'entityId' => trim($sourceEntityId),
						'availability' => (string)$element->availability,
						'label' => (string)$element->label[0],
//						'component'=> $element,
						'caption' => (string)$element->caption[0],
						'credit' => (string)$element->credit[0],
					];

			}
		}

		return  $newImplementedFigures;
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
	 * @param ?string $moleculeCode (optional) molecule of the atom
	 * @param ?string $domainCode (optional) domainCode of the atom
	 *
	 * @return array
	 */
	public static function comments($productId, $timezoneOffset = 0, $startTime = null, $endTime = null, $queriesOnly = false, $queryType = null, $moleculeCode = 'Any', $domainCode = 'Any') {

		$startTime = $startTime ? (int)$startTime : null;
		$endTime = $endTime ? (int)$endTime : null;
		list($startTime, $endTime) = self::_enforceRangeSanity($startTime, $endTime);

		//product_id field added in select so can double filter out by product.
		$atomSubQuery = Atom::select('entity_id', 'product_id', 'title', 'alpha_title', 'molecule_code', 'domain_code')
                ->where('product_id', '=', DB::raw((int)$productId))		//laravel doesn't like bindings in subqueries
				->whereIn('id', function ($q) {
					Atom::legacyBuildLatestIDQuery(null, $q); //uses legacyBuildLatestIDQuery because of subquery/binding complications
				});

		$rawAtomSubQuery = DB::raw('(' . $atomSubQuery->toSql() . ') AS atom_subquery');

		$query = Comment::select(
					'comments.*',
					'atom_subquery.entity_id AS entity_id',
					'atom_subquery.title AS title',
					'atom_subquery.alpha_title AS atom_title',
					'atom_subquery.molecule_code AS atom_molecule',
					'atom_subquery.domain_code AS atom_domain',
					'users.firstname AS firstname',
					'users.lastname AS lastname'
				)
		 		->where('atom_subquery.product_id', '=', DB::raw((int)$productId))		//NEED TO SORT OUT AGAIN BY PRODUCT. subquery does not do this
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

		if ($moleculeCode == 'Any' && $domainCode == 'Any'){ //moleculeCode is Any, DomainCode is Any
			$query->orderBy('comments.id', 'DESC');
		}else{
			if ($domainCode != 'Any'){//domainCode is not Any
				$query->where('atom_subquery.domain_code', '=', $domainCode);
				$query->orderBy('comments.id', 'DESC');
			}
			if ($moleculeCode != 'Any'){//moleculeCode is not Any
				$query->where('atom_subquery.molecule_code', '=', $moleculeCode);
				$query->orderBy('comments.id', 'DESC');
			}
		//	$query->orderBy('atom_subquery.alpha_title', 'DESC');//moleculeCode or domainCode is not Any
		}

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
	 * Builds a CSV from a list of queries.
	 *
	 * @param object[] $comments
	 * @param ?string $queryType (optional) Filter down to this type of query
	 *
	 * @return Response
	 */
	public static function buildFiguresCSV($comments, $queryType = null) {
		$headings = ['atom_title', 'query_type', 'text', 'firstname', 'lastname', 'created_at'];

		$queries = self::_extractQueries($newFigures, $queryType);

		return self::arrayToCsv('newFigures.csv', $headings, $queries);
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

		//Note: if product isn't defined in $productStats, the report output will be infinity instead
		$charsMean = $charsStdErr = $wordsMean = $wordsStdErr = 0;

		//these magic numbers need to be moved out into the products table when it exists
		$productStats = [
			//Skidmore NDR
			1 => [
				'charsMean' => 2826,
				'charsStdErr' => 445,
				'wordsMean' => 0,
				'wordsStdErr' => 0,
			],

			//Dental Dictionary
			5 => [
				'charsMean' => 3357,
				'charsStdErr' => 596,
				'wordsMean' => 509,
				'wordsStdErr' => 88,
			],

		];

		if (array_key_exists($productId, $productStats) ) {
			$charsMean = $productStats[$productId]['charsMean'];
			$charsStdErr = $productStats[$productId]['charsStdErr'];
			$wordsMean = $productStats[$productId]['wordsMean'];
			$wordsStdErr = $productStats[$productId]['wordsStdErr'];
		}

		$stats = [
			'pageStats' => [
				'charsMean' => $charsMean,
				'charsStdErr' => $charsStdErr,
				'wordsMean' => $wordsMean,
				'wordsStdErr' => $wordsStdErr,
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
	 * Provide an array of statistics about the suggested images.
	 *
	 * @param integer $productId Limit to this product
	 *
	 * @return array
	 */
	public static function suggestedImageStats($productId) {
		$suggestedImageStats = self::_countOfSuggestedImage($productId);

		$stats = [
			'suggestedImage' => $suggestedImageStats
		];

		return $stats;
	}

	/**
	 * Provide an array of statistics about the legacy images.
	 *
	 * @param integer $productId Limit to this product
	 *
	 * @return array
	 */
	public static function legacyImageStats($productId) {
		$legacyImageStats = self::_countOfLegacyImage($productId);

		$stats = [
			'legacyImage' => $legacyImageStats
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
	 * Get a robust list of all atoms keyed by their entity_id. Include id, modified by user and dates. Needed for reports.
	 *
	 * @param integer $productId Limit to this product
	 *
	 * @return object[]
	 */
	protected static function _getRobustKeyedAtoms($productId) {

		$results = Atom::select('id', 'entity_id', 'molecule_code', 'title', 'alpha_title', 'domain_code', 'modified_by', 'created_at', 'updated_at', 'deleted_at', 'xml')
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
		ini_set('memory_limit', '1280M');
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


	/**
     * Calculate counts for suggested images.
	 *
	 * @param integer $productId Limit to this product
     *
     * @return integer[]
     */
    protected static function _countOfSuggestedImage($productId) {
		$stats = [];
		$stats['sum'] = [
			'implemented' => 0,
			'accept' => 0,
			'pending' => 0,
			'reject' => 0,
			'' => 0            //for image without availability
		];
		$deactivatedStatus = Status::getDeactivatedStatusId($productId);

		//for implemented images
		$implementedSql = 'select id, entity_id, alpha_title, xml from atoms
			where id in
				(select MAX(id) from atoms
				where product_id = '. $productId.'
					and deleted_at is null
					and status_id != '.$deactivatedStatus->id.'
				GROUP BY entity_id)
			and xml like '."'".'%<file src='.'"'.'suggested/%'."'".
			' and product_id = '. $productId.'
			and deleted_at is null
			and status_id != '.$deactivatedStatus->id;

        $implementedQuery = DB::select($implementedSql);
        $implementedArray = json_decode(json_encode($implementedQuery), true);

        foreach ($implementedArray as $implemented){
			$ob = simplexml_load_string($implemented['xml']);
			$figureNodes = $ob->$implemented['xml']->xpath('//component[@type="figure"]');
			if($figureNodes){
				$figureNodes = json_encode($figureNodes);
				$figureNodes = (array)json_decode($figureNodes, true);

				foreach ($figureNodes as $figureNode){
					$file = isset($figureNode['file']['@attributes']['src']) ? $figureNode['file']['@attributes']['src'] : '';
					if (substr($file, 0, 9) == 'suggested'){ //if it's suggested image
						$availability = isset($figureNode['@attributes']['availability']) ? $figureNode['@attributes']['availability'] : '';
						$caption = isset($figureNode['caption']) ? $figureNode['caption'] : '';
						$credit = isset($figureNode['credit']) ? $figureNode['credit'] : '';
						$title = isset($figureNode['comp_title']) ? $figureNode['comp_title'] : '';
						$label = isset($figureNode['label']) ? $figureNode['label'] : '';

						$stats[] = [
							'entity_id' => $implemented['entity_id'],
							'alpha_title' => $implemented['alpha_title'],
							'availability' => $availability,
							'file' => $file,
							'caption' => $caption,
							'credit' => $credit,
							'title' => $title,
							'label' => $label,
							'imageStatus' => 'implemented'
						];

						$stats['sum']['implemented'] += 1;
					}
				}
			}
		}

		//for pending/accepted/rejected images
		$commentsSql = 'select * from comments c
			join atoms a on c.atom_entity_id=a.entity_id
			where a.id in
				(
					select MAX(id) from atoms
						where product_id ='. $productId.'
							and deleted_at is null
							and status_id != '.$deactivatedStatus->id.'
						Group By entity_id
				)
			and a.product_id ='. $productId.'
			and a.deleted_at is null
			and a.status_id	 != '.$deactivatedStatus->id.'
			and c.text like '."'".'%type="figure"%'."'";

		$commentsQuery = DB::select($commentsSql);
		$commentsArray = json_decode(json_encode($commentsQuery), true);
		foreach ($commentsArray as $comment){
			$entityId = $comment['entity_id'];
			$alphaTitle = $comment['alpha_title'];
			$ob = simplexml_load_string($comment['text']);
			$queryNodes = $ob->$comment['text']->xpath('//query[@type="figure"]');
			if($queryNodes){
				$suggestionNodes = $queryNodes[0]->xpath('//suggestion//text()');
				$suggestionNodes = json_encode($suggestionNodes);
                $suggestionNodes = (array)json_decode($suggestionNodes, true);
                $imageStatus = '';
                foreach ($suggestionNodes as $suggestionNode){
                    if (isset($suggestionNode[0])){
                        $imageStatus = $suggestionNode[0];
                    }
                }
				if ($imageStatus != 'implemented'){  //for pending, accepted, rejected images
					$availabilityNodes = $queryNodes[0]->xpath('//availability//text()');
					$availabilityNodes = json_encode($availabilityNodes);
					$availabilityNodes = (array)json_decode($availabilityNodes, true);
					$availability = '';
					foreach ($availabilityNodes as $availabilityNode){
						if (isset($availabilityNode[0])){
							$availability = $availabilityNode[0];
						}
					}

					$figureNodes = $queryNodes[0]->xpath('//component[@type="figure"]');
					if ($figureNodes){
						foreach ($figureNodes as $figureNode){
							$figureNode = json_encode($figureNode);
							$figureNode = json_decode($figureNode, true);
							$file = isset($figureNode['file']['@attributes']['src']) ? $figureNode['file']['@attributes']['src'] : '';
							$credit = isset($figureNode['credit']) ? $figureNode['credit'] : '';
							$title = isset($figureNode['comp_title']) ? $figureNode['comp_title'] : '';
							$label = isset($figureNode['label']) ? $figureNode['label'] : '';

							$caption1 = isset($figureNode['caption']) ? $figureNode['caption'] : '';
							$caption2 = isset($figureNode['ce_caption']) ? $figureNode['ce_caption'] : '';
							$caption = $caption1 == '' ? $caption2 : $caption1;

							$stats[] = [
							'entity_id' => $entityId,
							'alpha_title' => $alphaTitle,
							'availability' => $availability,
							'file' => $file,
							'caption' => $caption,
							'credit' => $credit,
							'title' => $title,
							'label' => $label,
							'imageStatus' => $imageStatus
							];

							$stats['sum'][$imageStatus] += 1;
						}
					}
				}
			}
		}
        return $stats;
    }

	/**
     * Calculate counts for legacy images.
	 *
	 * @param integer $productId Limit to this product
     *
     * @return integer[]
     */
    protected static function _countOfLegacyImage($productId) {
		$stats = [];
		$stats['sum'] = [];
		$chapter_arr = range('A', 'Z');
		array_push($chapter_arr, 'None');

		$stats['electronic'] = 0;
		$stats['print'] = 0;
		$stats['both'] = 0;
		$stats['none'] = 0;
		$stats['total'] = 0;

		foreach ($chapter_arr as $chapter){
			$stats['sum'][$chapter]['electronic']= 0;
			$stats['sum'][$chapter]['print']= 0;
			$stats['sum'][$chapter]['both']= 0;
			$stats['sum'][$chapter]['none']= 0;
			$stats['sum'][$chapter]['chapter'] = $chapter;
			$stats['sum'][$chapter]['total'] = 0;
		}
		$deactivatedStatus = Status::getDeactivatedStatusId($productId);

		//for implemented images
		$sql = 'select id, entity_id, molecule_code, alpha_title, xml from atoms
			where id in
				(select MAX(id) from atoms
				where product_id = '. $productId.'
					and deleted_at is null
					and status_id != '.$deactivatedStatus->id.'
				GROUP BY entity_id)
			and xml like '."'".'%<component type="figure"%'."'".
			' and product_id = '. $productId.'
			and deleted_at is null
			and status_id != '.$deactivatedStatus->id;
        $query = DB::select($sql);
        $arr = json_decode(json_encode($query), true);

        foreach ($arr as $atom){
			$atom['molecule_code'] = $atom['molecule_code'] == null ? 'None' : $atom['molecule_code'];
			$ob = simplexml_load_string($atom['xml']);
			$figureNodes = $ob->$atom['xml']->xpath('//component[@type="figure"]');
			if($figureNodes){
				$figureNodes = json_encode($figureNodes);
				$figureNodes = (array)json_decode($figureNodes, true);

				foreach ($figureNodes as $figureNode){
					$file = isset($figureNode['file']['@attributes']['src']) ? $figureNode['file']['@attributes']['src'] : '';
					if (substr($file, 0, 9) != 'suggested'){ //if it's not suggested image
						$availability = isset($figureNode['@attributes']['availability']) ? $figureNode['@attributes']['availability'] : 'none';
						if (isset($stats['sum'][$atom['molecule_code']][$availability])){
							$stats['sum'][$atom['molecule_code']][$availability] += 1;
							$stats['sum'][$atom['molecule_code']]['molecule'] = $atom['molecule_code'];
							$stats['sum'][$atom['molecule_code']]['total'] += 1;
							$stats['total'] += 1;

							switch ($availability){
								case 'electronic':
									$stats['electronic'] += 1;
									break;
								case 'print':
									$stats['print'] += 1;
									break;
								case 'both':
									$stats['both'] += 1;
									break;
								case 'none':
									$stats['none'] += 1;
									break;
								default:
									break;

							}
						}
					}
				}
			}
		}
        return $stats;
    }
}