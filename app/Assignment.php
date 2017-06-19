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
	 * @param integer $productId Limit query to this product
	 * @param ?array $filters The filters as key => value pairs
	 * @param ?array $order (optional) The order column and direction
	 * @param ?int $limit (optional) Max number of results per page
	 * @param int $page (optional) The results page to retrieve
	 * @param boolean $addAtoms (optional) Add associated atoms to the assignments?
	 *
	 * @return array The list of assignments
	 */
	public function getList($productId, $filters, $order = [], $limit = null, $page = 1, $addAtoms = false) {
		$columns = $this->getMyColumns();
		array_unshift($columns, DB::raw('COUNT(comments.text) AS count'));
		$query = self::allForProduct($productId)->select($columns);
		self::_addListFilters($query, $filters);
		self::_addOrder($query, $order);

		$countQuery = clone $query->getQuery();
		$countQuery->select(DB::raw('COUNT(*)'));
		$count = sizeof($countQuery->get());

		//paginate the results
		if($limit) {
			$query->skip($limit * ($page - 1))->take($limit);
		}

		$assignments = $query->get()
				->toArray();

		//Laravel's built-in hasOne functionality won't work on atoms
		if($addAtoms) {
			$entityIds = array_column($assignments, 'atom_entity_id');
			$atoms = Atom::findNewest($entityIds, $productId)
					->get();
			Comment::addSummaries($atoms, $productId);
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
	 * @param integer $productId Limit query to this product
	 *
	 * @return ?object The assignment (if found)
	 */
	public static function getCurrentAssignment($atomEntityId, $productId) {
		$assignment = self::allForProduct($productId)
				->orderBy('assignments.id', 'DESC')
				->where('assignments.atom_entity_id', '=', $atomEntityId)
				->groupBy('assignments.id')
				->limit(1)
				->first();
		
		return ($assignment && $assignment->task_end) ? null : $assignment;
	}

	/**
	 * Add filters to the list query.
	 *
	 * @param object $query The query object to modify
	 * @param mixed[] $filters The filters to add represented as key => value pairs
	 */
	protected static function _addListFilters($query, $filters) {
		$validFilters = ['task_id', 'atoms.molecule_code', 'atoms.domain_code', 'assignments.user_id', 'user_id', 'atom_entity_id', 'task_ended', 'has_discussion'];
		if($filters) {
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
					else if ($validFilter == 'has_discussion'){
						if ($filterValue == 1){
							$query->having(DB::raw('COUNT(comments.text)'), '>', 0);
						}else if ($filterValue == 0){
							$query->having(DB::raw('COUNT(comments.text)'), '=', 0);
						}
					}
					else if ($validFilter == 'atom_entity_id'){
						$query->where('assignments.atom_entity_id', '=', $filterValue);
					}
					else if ($validFilter == 'user_id'){
						$query->where('assignments.user_id', '=', $filterValue);
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
		}
		else {
			$order = [
				'column' => 'assignments.id',
				'direction' => 'asc'
			];
		}

		$query->orderBy($order['column'], $order['direction'])
				->groupBy($order['column']);
	}

	/**
	 * Update the atom's assignments.
	 *
	 * @param string $atomEntityId The atom's entityId
	 * @param mixed[] $promotion The promotion we're going to perform
	 * @param integer $productId Limit query to this product
	 */
	public static function updateAssignments($atomEntityId, $promotion, $productId) {
		$allowedProperties = ['atom_entity_id', 'user_id', 'task_id', 'task_end'];

		$user = \Auth::user();
		if(array_key_exists('task_id', $promotion)) {		//not all promotions touch the assignments table
			self::_endCurrentAssignment($atomEntityId, $productId);

			//create a new assignment if this isn't a terminal promotion
			if($promotion['task_id']) {
				$assignment = new Assignment();
				foreach($allowedProperties as $allowed) {
					if(array_key_exists($allowed, $promotion)) {
						$assignment->$allowed = $promotion[$allowed];
					}
				}
				$assignment->created_by = $user->id;
				$assignment->task_id = $promotion['task_id'];
				$assignment->atom_entity_id = $atomEntityId;

				$assignment->save();
			}
		}
		else if(array_key_exists('user_id', $promotion) && $promotion['user_id']) {		//change assignment's owner
			$assignment = self::getCurrentAssignment($atomEntityId, $productId);
			if($assignment) {
				self::_endCurrentAssignment($atomEntityId, $productId);
				$assignment = $assignment->replicate();
				$assignment->created_by = $user->id;
				$assignment->user_id = $promotion['user_id'];
				$assignment->save();
			}
		}
	}

	/**
	 * End the current task if it's still open.
	 *
	 * @param string $atomEntityId The atom's entityId
	 * @param integer $productId Limit query to this product
	 */
	protected static function _endCurrentAssignment($atomEntityId, $productId) {
		$currentAssignment = self::getCurrentAssignment($atomEntityId, $productId);
		if($currentAssignment && !$currentAssignment->task_end) {
			$currentAssignment->task_end = DB::raw('CURRENT_TIMESTAMP');
			$currentAssignment->save();
		}
	}

	/**
	 * Find a user's next assignment.
	 *
	 * @param integer $userId The user's ID
	 * @param string $atomEntityId The atomEntityId the user is currently on
	 * @param integer $productId Limit query to this product
	 *
	 * @return ?object
	 */
	public static function next($userId, $atomEntityId, $productId) {
		$query = Assignment::allForProduct($productId)
				->where('assignments.user_id' , '=', $userId)
				->groupBy('assignments.id')
				->orderBy('assignments.id', 'ASC');
		$assignments = $query->get();

		//find the current assignment
		$found = 0;
		for($i = $assignments->count() - 1; $i >= 0; --$i) {
			if($assignments->get($i)->atom_entity_id == $atomEntityId) {
				$found = $i;
				break;
			}
		}

		//find the next open assignment
		for($i = $found + 1; $i < $assignments->count(); ++$i) {
			if($assignments->get($i)->task_end === null) {
				return new ApiPayload($assignments->get($i));
			}
		}

		return null;
	}

	/**
	 * Select all that belong to the specified product.
	 *
	 * @param integer $productId Limit to this product
	 *
	 * @return object The query object
	 */
	public static function allForProduct($productId) {
		return self::select('assignments.*')
				->join('atoms', 'assignments.atom_entity_id', '=', 'atoms.entity_id')
				->leftJoin('comments', 'assignments.atom_entity_id', '=', 'comments.atom_entity_id')
				->where('atoms.product_id', '=', (int)$productId)
				->groupBy('atoms.entity_id');
	}
}
