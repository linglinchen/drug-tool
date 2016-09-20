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
	 * Get a count of how many atoms were edited per day.
	 *
	 * @return integer[]
	 */
	public static function edits() {
		$results = Atom::select(DB::raw('COUNT(DISTINCT entity_id)'), DB::raw('DATE_TRUNC(\'day\', created_at) AS day'))
				->groupBy(DB::raw('DATE_TRUNC(\'day\', created_at)'))
				->get();

		$output = [];
		foreach($results as $row) {
			$output[strtotime($row['day'])] = $row['count'];
		}

		return $output;
	}
}