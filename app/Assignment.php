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
			$entityIds = array_column($assignments, 'atomEntityId');
			$atoms = Atom::findNewest($entityIds)
					->get();
			self::_addCommentSummaries($atoms);
			$atoms = $atoms->toArray();

			//remove xml
			foreach($atoms as $atomKey => $atom) {
				unset($atom['xml']);		//a waste of bandwidth in this case
			}

			foreach($assignments as &$row) {
				foreach($atoms as $atomKey => $atom) {
					if($atom['entityId'] == $row['atomEntityId']) {
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
	 * Add a summary of comments to an atom collection.
	 *
	 * @param object $atoms The atom collection
	 *
	 * @return object This object
	 */
	protected function _addCommentSummaries($atoms) {
		$groupedComments = [];
		$commentSummaries = [];
		$entityIds = array_unique($atoms->pluck('entityId')->toArray());
		$comments = Comment::getByAtomEntityId($entityIds);

		foreach($comments as $comment) {
			if(!isset($groupedComments[$comment['atomEntityId']])) {
				$groupedComments[$comment['atomEntityId']] = [];
			}

			$groupedComments[$comment['atomEntityId']][] = $comment;
		}

		foreach($groupedComments as $entityId => $group) {
			$commentSummaries[$entityId] = [
					'count' => sizeof($group),
					'lastComment' => [
						'date' => sizeof($group) ? $group[0]['created_at'] : null,
						'userId' => sizeof($group) ? $group[0]['userId'] : null
					]
				];
		}

		foreach($atoms as $atom) {
			$atom->commentSummary = isset($commentSummaries[$atom->entityId]) ? $commentSummaries[$atom->entityId] : null;
		}
	}

	/**
	 * Get the specified atom's currently active assignment.
	 *
	 * @param string $entityId The atom's entityId
	 *
	 * @return ?object The assignment (if found)
	 */
	public static function getCurrentAssignment($entityId) {
		$assignment = self::find(1)
				->orderBy('id', 'DESC')
				->where('atomEntityId', '=', $entityId)
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
		$validFilters = ['taskId', 'atoms.moleculeCode', 'userId', 'atomEntityId', 'taskEnded'];

		if($filters) {
			self::_joinAtoms($query);

			foreach($validFilters as $validFilter) {
				if(isset($filters[$validFilter])) {
					$filterValue = $filters[$validFilter];

					if($validFilter == 'taskEnded') {
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
		$query->leftJoin('atoms', 'assignments.atomEntityId', '=', 'atoms.entityId')
				->whereIn('atoms.id', $currentAtomIds);
	}

	/**
	 * Perform workflow promotions.
	 *
	 * @param string $atomEntityIds The atoms' entityIds
	 * @param mixed[] $promotion The promotion we're going to perform
	 *
	 * @return mixed[] The
	 */
	public static function promote($atomEntityIds, $promotion) {
        $atomEntityIds = array_unique($atomEntityIds);		//no need to promote twice

        $atoms = [];
        foreach($atomEntityIds as $atomEntityId) {
            $atom = Atom::findNewestIfNotDeleted($atomEntityId);
            if($atom) {
                continue;       //skip atoms that don't exist
            }

            self::_updateAssignments($atomEntityId, $promotion);

            //we might need to update the atom
            if(isset($promotion['statusId'])) {
                $atom->statusId = $promotion['statusId'];
                $atom->save();
            }

            $atom = $atom->addAssignments()->toArray();
            unset($atom['xml']);
            $atoms[] = $atom;
        }

        return $atoms;
    }

	/**
	 * Update the atom's assignments.
	 *
	 * @param string $atomEntityId The atom's entityId
	 * @param mixed[] $promotion The promotion we're going to perform
	 */
	protected static function _updateAssignments($atomEntityId, $promotion) {
		if(isset($promotion['taskId'])) {		//not all promotions touch the assignments table
			if($promotion['taskId']) {		//terminal promotions have empty taskIds
				$assignment = new Assignment();
				foreach($this->_allowedProperties as $allowed) {
					if(array_key_exists($allowed, $promotion)) {
						$assignment->$allowed = $promotion[$allowed];
					}
				}
				$assignment->createdBy = $user->id;
				$assignment->taskId = $promotion['taskId'];
				$assignment->atomEntityId = $atomEntityId;

				$assignment->save();
			}

			self::_endCurrentAssignment($atomEntityId);
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
