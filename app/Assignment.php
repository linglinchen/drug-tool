<?php

namespace App;

use DB;

use App\AppModel;
use App\Atom;
use App\Comment;

class Assignment extends AppModel {
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
	 * @param ?int $limit (optional) Max number of results per page
	 * @param int $page (optional) The results page to retrieve
	 * @param boolean $addAtoms (optional) Add associated atoms to the assignments?
	 *
	 * @return array The list of assignments
	 */
	public function getList($filters, $order = [], $limit = null, $page = 1, $addAtoms = false) {
		$columns = $this->getMyColumns();
		$query = self::select($columns);
		self::_addListFilters($query, $filters);
		self::_addOrder($query, $order);
		$count = $query->count();

		//paginate the results
		if($limit) {
			$query->skip($limit * ($page - 1))->take($limit);
		}

		$assignments = $query->get()
				->toArray();

		//Laravel's built-in hasOne functionality won't work on atoms
		if($addAtoms) {
			$entityIds = array_column($assignments, 'atom_entity_id');
			$atoms = Atom::findNewest($entityIds)
					->get();
			Comment::addSummaries($atoms);
			$atoms = $atoms->toArray();

			//remove xml
			foreach($atoms as $atomKey => $atom) {
				unset($atom['xml']);		//a waste of bandwidth in this case
			}

			foreach($assignments as &$row) {
				foreach($atoms as $atomKey => $atom) {
					if($atom['entity_id'] == $row['atom_entity_id']) {
						$row['atom'] = $atom;
						break;
					}
				}
			}
		}

		return [
			'assignments' => $assignments,
			'count' => $count
		];
	}

	/**
	 * Get the specified atom's currently active assignment.
	 *
	 * @param string $atomEntityId The atom's entityId
	 *
	 * @return ?object The assignment (if found)
	 */
	public static function getCurrentAssignment($atomEntityId) {
		$assignment = self::find(1)
				->orderBy('id', 'DESC')
				->where('atom_entity_id', '=', $atomEntityId)
				->first();

		return ($assignment && $assignment->taskEnd) ? null : $assignment;
	}

	/**
	 * Add filters to the list query.
	 *
	 * @param object $query The query object to modify
	 * @param mixed[] $filters The filters to add represented as key => value pairs
	 */
	protected static function _addListFilters($query, $filters) {
		$validFilters = ['task_id', 'atoms.moleculeCode', 'user_id', 'atom_entity_id', 'task_ended'];

		if($filters) {
			self::_joinAtoms($query);

			foreach($validFilters as $validFilter) {
				if(isset($filters[$validFilter])) {
					$filterValue = $filters[$validFilter] === '' ? null : $filters[$validFilter];

					if($validFilter == 'task_ended') {
						if($filterValue) {
							$query->whereNotNull('task_end');
						}
						else {
							$query->whereNull('task_end');
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
	 * @fixme Ordering broke somehow...
	 *
	 * @param object $query The query object to modify
	 * @param string[] $order The order column and direction
	 */
	protected static function _addOrder($query, $order) {
		if($order && isset($order['column'])) {
			$order['direction'] = isset($order['direction']) && strtolower($order['direction']) == 'desc' ?
					'desc' :
					'asc';

			$query->orderBy($order['column'], $order['direction'])
					->groupBy($order['column']);
		}
	}

	/**
	 * Join in the current versions of the atoms.
	 *
	 * @param object $query The query object to modify
	 */
	protected static function _joinAtoms($query) {
		$currentAtomIds = Atom::latestIDs();
		$query->leftJoin('atoms', 'assignments.atom_entity_id', '=', 'atoms.entity_id')
				->whereIn('atoms.id', $currentAtomIds);
	}

	/**
	 * Update the atom's assignments.
	 *
	 * @param string $atomEntityId The atom's entityId
	 * @param mixed[] $promotion The promotion we're going to perform
	 */
	public static function updateAssignments($atomEntityId, $promotion) {
		$allowedProperties = ['atom_entity_id', 'user_id', 'task_id', 'task_end'];

		$user = \Auth::user();
		if(isset($promotion['task_id'])) {		//not all promotions touch the assignments table
			self::_endCurrentAssignment($atomEntityId);

			//create a new assignment if this isn't a terminal promotion
			if($promotion['task_id']) {
				$assignment = new Assignment();
				foreach($allowedProperties as $allowed) {
					if(array_key_exists($allowed, $promotion)) {
						$assignment->$allowed = $promotion[$allowed];
					}
				}
				$assignment->createdBy = $user->id;
				$assignment->taskId = $promotion['task_id'];
				$assignment->atomEntityId = $atomEntityId;

				$assignment->save();
			}
		}
		else if(isset($promotion['user_id']) && $promotion['user_id']) {		//change assignment's owner
			$assignment = self::getCurrentAssignment($atomEntityId);
			if($assignment) {
				self::_endCurrentAssignment($atomEntityId);
				$assignment = $assignment->replicate();
				$assignment->createdBy = $user->id;
				$assignment->userId = $promotion['user_id'];
				$assignment->save();
			}
		}
	}

	/**
	 * End the current task if it's still open.
	 *
	 * @param string $atomEntityId The atom's entityId
	 */
	protected static function _endCurrentAssignment($atomEntityId) {
		$currentAssignment = self::getCurrentAssignment($atomEntityId);
		if($currentAssignment && !$currentAssignment->taskEnd) {
			$currentAssignment->taskEnd = DB::raw('CURRENT_TIMESTAMP');
			$currentAssignment->save();
		}
	}
}
