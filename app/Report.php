<?php

namespace App;

use DB;
use Log;

use App\AppModel;
use App\Atom;

class Report extends AppModel {
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
	 * @return array
	 */
	public static function edits() {
		$results = Atom::select(
					'modified_by',
					DB::raw('DATE_TRUNC(\'day\', created_at) AS x'),
					DB::raw('COUNT(DISTINCT entity_id) AS y')
				)
				->groupBy(
					'modified_by',
					DB::raw('DATE_TRUNC(\'day\', created_at)')
				)
				->orderBy(DB::raw('DATE_TRUNC(\'day\', created_at)'))
				->get();

		if(sizeof($results)) {
			$startTime = strtotime($results[0]->x);
			$endTime = strtotime($results[sizeof($results) - 1]->x);

			$stepSize = 24 * 60 * 60;		//1 day
			$blankSeries = [];
			for($i = $startTime; $i <= $endTime; $i += $stepSize) {
				$blankSeries[$i] = [
					'x' => $i,
					'y' => 0
				];
			}
		}

		$output = [];
		foreach($results as $row) {
			$userId = (int)$row['modified_by'];
			unset($row['modified_by']);

			if(!isset($output[$userId])) {
				$output[$userId] = $blankSeries;
			}

			$time = strtotime($row->x);
			$output[$userId][$time] = [
				'x' => $time,
				'y' => $row->y
			];
		}

		return $output;
	}
}