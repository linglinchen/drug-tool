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
	 * @param ?array $order (optional) The order column and direction
	 * @param boolean $addAtoms (optional) Add associated atoms to the assignments?
	 *
	 * @return array The list of assignments
	 */
	public static function getList($filters, $order = [], $addAtoms = false) {
		$output = self::select();
		self::_addListFilters($output, $filters);
		self::_addOrder($output, $order);
		$output = $output->get()
				->toArray();

		//Laravel's built-in hasOne functionality won't work on atoms
		if($addAtoms) {
			$entityIds = array_column($output, 'atomEntityId');
			$atoms = Atom::findNewest($entityIds)
					->get()
					->toArray();

			//remove xml
			foreach($atoms as $atomKey => $atom) {
				unset($atom['xml']);		//a waste of bandwidth in this case
			}

			foreach($output as &$row) {
				foreach($atoms as $atomKey => $atom) {
					if($atom['entityId'] == $row['atomEntityId']) {
						$row['atom'] = $atom;
						break;
					}
				}
			}
		}

		return $output;
	}

	/**
	 * Add filters to the list query.
	 *
	 * @param object $query The query object to modify
	 * @param mixed[] $filters The filters to add represented as key => value pairs
	 */
	protected static function _addListFilters($query, $filters) {
		$validFilters = ['taskId', 'statusId', 'userId', 'atomEntityId', 'taskEnded'];

		if($filters) {
			foreach($validFilters as $validFilter) {
				if(isset($filters[$validFilter])) {
					$filterValue = $filters[$validFilter];

					if($validFilter === 'taskEnded') {
						if($filterValue) {
							$query->whereNotNull('taskEnd');
						}
						else {
							$query->whereNull('taskEnd');
						}
					}
					else {
						$query->where($validFilter, '=', $filterValue);
					}
				}
			}
		}
	}

	/**
	 * Order the list query.
	 *
	 * @param object $query The query object to modify
	 * @param string[] $order The order column and direction
	 */
	protected static function _addOrder($query, $order) {
		if($order && isset($order['column'])) {
			$order['direction'] = isset($order['direction']) && strtolower($order['direction']) == 'desc' ?
					'desc' :
					'asc';

			$query->orderBy($order['column'], $order['direction']);
		}
	}
}
