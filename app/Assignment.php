<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

use App\Atom;

class Assignment extends Model
{
	protected $table = 'assignments';
	protected $guarded = ['id'];
	protected $dates = ['created_at', 'updated_at'];

	/**
	 * GET a list of all assignments or POST filters to retrieve a filtered list.
	 * Adds the appropriate atoms.
	 *
	 * @api
	 *
	 * @param ?array $filters The filters as key => value pairs
	 * @param ?array $order The order column and direction
	 *
	 * @return array The list of assignments
	 */
	public static function getList($filters, $order) {
		$output = self::select();
		self::_addListFilters($output, $filters);
		self::_addOrder($output, $order);
		$output = $output->get()
				->toArray();

		//Laravel's built-in hasOne functionality won't work on atoms
		$entityIds = array_column($output, 'atomEntityId');
		$atoms = Atom::findNewest($entityIds)
				->get()
				->toArray();
		foreach($output as &$row) {
			foreach($atoms as $atomKey => $atom) {
				if($atom['entityId'] == $row['atomEntityId']) {
					unset($atom['xml']);		//a waste of bandwidth in this case
					$row['atom'] = $atom;
					unset($atoms[$atomKey]);		//for performance
				}
			}
		}

		return $output;
	}

	protected static function _addListFilters($query, $filters) {
		if($filters) {
			if(isset($filters['taskId'])) {
				$query->where('taskId', '=', $filters['taskId']);
			}

			if(isset($filters['statusId'])) {
				$query->where('statusId', '=', $filters['statusId']);
			}

			if(isset($filters['userId'])) {
				$query->where('userId', '=', $filters['userId']);
			}

			if(isset($filters['active'])) {
				$query->where('active', '=', $filters['active']);
			}
		}
	}

	protected static function _addOrder($query, $order) {
		if($order && isset($order['column'])) {
			$order['direction'] = isset($order['direction']) && strtolower($order['direction']) == 'desc' ?
					'desc' :
					'asc';

			$query->orderBy($order['column'], $order['direction']);
		}
	}
}
