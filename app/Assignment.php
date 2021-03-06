<?php

namespace App;

use DB;

use App\AppModel;
use App\Atom;
use App\Comment;
use App\Status;

class Assignment extends AppModel {
    protected $table = 'assignments';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
    protected static $validFilters = [
        'task_id',
        'assignments.user_id',
        'user_id',
        'atom_entity_id',
        'task_ended',
        'has_discussion',
        'has_figures',
        'atoms.domain_code',
        'status_id', /*JIRA 1036 TC*/ 
    ];


    /**
     * GET a list of all assignments or POST filters to retrieve a filtered list.
     * Adds the appropriate atoms.
     *
     * @param integer $productId Limit query to this product
     * @param ?array $filters The filters as key => value pairs
     * @param ?string $queryString The user's search query
     * @param ?array $order (optional) The order column and direction
     * @param ?int $limit (optional) Max number of results per page
     * @param int $page (optional) The results page to retrieve
     * @param boolean $addAtoms (optional) Add associated atoms to the assignments?
     *
     * @return array The list of assignments
     */
    public function getList($productId, $filters, $queryString, $order = [], $limit = null, $page = 1, $addAtoms = false) {
        $columns = $this->getMyColumns();
        array_unshift($columns, DB::raw('COUNT(comments.text) AS count'));
        $query = self::allForProduct($productId)->select($columns);
        self::_addListFilters($query, $queryString, $filters, $productId);

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
            foreach($atoms as $atom) {
                $atom->addDomains($productId);
                $atom->addCommentSuggestions($atom['entity_id']);
                //$atom->addStatus($productId); /*JIRA 1036 TC*/
            }
            $atoms = $atoms->toArray();

            //remove xml
            foreach($atoms as $atomKey => $atom) {
                unset($atom['xml']);        //a waste of bandwidth in this case
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
     * Get the assignment by assignmentId.
     *
     * @param string $assignmentId The assignment id
     * @param integer $productId Limit query to this product
     *
     * @return ?object The assignment (if found)
     */
    public static function getAssignment($assignmentId, $productId) {
        $assignment = self::allForProduct($productId)
                ->orderBy('assignments.id', 'DESC')
                ->where('assignments.id', '=', $assignmentId)
                ->groupBy('assignments.id')
                ->limit(1)
                ->first();

        return ($assignment && $assignment->task_end) ? null : $assignment;
    }

/*Start JIRA 1036 TC - from lines 96 to 113*/

/*End JIRA 1036 TC*/

    /**
     * Add filters to the list query.
     *
     * @param object $query The query object to modify
     * @param string ?$queryString The user's search query
     * @param ?array $filters The filters as key => value pairs
     * @param mixed[] $filters The filters to add represented as key => value pairs
     */
    protected static function _addListFilters($query, $queryString, $filters, $productId) {
        if(!$filters) {
            return;
        }

        $atomFilters = self::_getAtomFilters($filters);

        if($atomFilters || $queryString) {
            $candidates = Atom::getSearchCandidates($queryString, $productId, $atomFilters);
            $query->whereIn('atoms.id', array_keys($candidates));
        }
        else {
            //make sure we aren't including old versions of the atoms
            $query->whereIn('atoms.id', function ($q) {
                Atom::buildLatestIDQuery(null, $q);
            });
        }

        foreach(self::$validFilters as $validFilter) {
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
                    }
                    else if ($filterValue == 0){
                        $query->having(DB::raw('COUNT(comments.text)'), '=', 0);
                    }
                    else if($filterValue == 4){
                        //has suggested figures
                        $query->join(DB::raw("(SELECT
                                    comments.text,
                                    comments.atom_entity_id
                                    FROM comments
                                    GROUP BY comments.atom_entity_id, comments.text
                                    ) as commentstemp"),function($join){
                                        $join->on("commentstemp.atom_entity_id","=","atoms.entity_id");
                                })
                                ->where('commentstemp.text', 'LIKE', '%<suggestion>pending%')
                                ->groupBy("commentstemp.atom_entity_id");
                    }
                }
                // has any figures in the atom record
                else if ($validFilter == 'has_figures'){
                    $query->whereIn('atoms.id', function ($q) use ($productId) {
                        $q->select(DB::raw('MAX(id)'))
                                ->from('atoms');
                        $q->where('product_id', '=', $productId);
                        $q->groupBy('entity_id');
                    });
                    if($filterValue > 1){
                        //do nothing
                        return;
                    }
                    else if ($filterValue == 1){
                        $query->where(function($q){
                            $q->where('atoms.xml', 'LIKE', '%type="figure"%')
                                ->orWhere('atoms.xml', 'LIKE', '%<img %');
                        });
                        return;
                    }
                    else if($filterValue < 1){
                        $query->where('atoms.xml', 'NOT LIKE', '%type="figure"%')
                            ->Where('atoms.xml', 'NOT LIKE', '%<img %');
                        return;
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

    /**
     * Process atom filters into an array.
     *
     * @param mixed[] $filters The filters to add represented as key => value pairs
     *
     * @return mixed[] The atom filters
     */
    protected static function _getAtomFilters($filters) {
        $atomFilters = [];

        foreach($filters as $key => $value) {
            if(!preg_match('/^atoms\./', $key)) {
                continue;
            }

            $atomKey = preg_replace('/^atoms\./', '', $key);
            $atomFilters[$atomKey] = $value;
        }

        return $atomFilters;
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
        $currentAssignment = self::allForProduct($productId)  //current assignment for the user
                ->orderBy('assignments.id', 'DESC')
                ->where('assignments.atom_entity_id', '=', $atomEntityId)
                ->where('assignments.user_id', '=', $user->id)
                ->whereNull('task_end')
                ->groupBy('assignments.id')
                ->limit(1)
                ->first();

        if (!$currentAssignment){ //no current asignment for this user
            $currentAssignment = self::getCurrentAssignment($atomEntityId, $productId);
        }

        if(array_key_exists('task_id', $promotion)) {
            if ($currentAssignment){
                if(array_key_exists('maxId', $currentAssignment)){
                    unset($currentAssignment->maxId);
                }
                if ($currentAssignment->task_id == $promotion['task_id']){ //mass assignment
                    self::_changeAssignmentOwner($atomEntityId, $productId, $promotion);
                }
                else{
                    if (self::_parallelAssignment($currentAssignment, $productId)){ //there's parallel assignment, only end the current Assignment
                        if(!$currentAssignment->task_end &&
                        !array_key_exists('assignment_ids', $promotion) &&
                        $currentAssignment->user_id == $user->id) { //it's not from mass assignment
                            $currentAssignment->task_end = DB::raw('CURRENT_TIMESTAMP');
                            $currentAssignment->save();
                            exit;
                        }
                    }
                    else if ($promotion['task_id']){ //no parallel assignment, and it's not the terminal promotion
                        self::_makeNewAssignment($currentAssignment, $allowedProperties, $promotion, $atomEntityId, $user);
                    }
                    else{ //task_id == null , for terminal promotion
                        if(!$currentAssignment->task_end) { //it's not from mass assignment
                            $currentAssignment->task_end = DB::raw('CURRENT_TIMESTAMP');
                            $currentAssignment->save();
                        }
                    }
                }
            }else{
                if($promotion['task_id']){
                    self::_makeNewAssignment($currentAssignment, $allowedProperties, $promotion, $atomEntityId, $user);
                }
            }
        }
        else if(array_key_exists('user_id', $promotion) && $promotion['user_id']) {        //change assignment's owner
            self::_changeAssignmentOwner($atomEntityId, $productId, $promotion);
        }
    }

    /**
     * create a new assignment
     *
     * @param object $currentAssignment the current unfinished assignment
     * @param object $allowedProperties the properties of table that's needed
     * @param object string $atomEntityId The atom's entityId
     * @param mixed[] $promotion The promotion we're going to perform
     * @param integer $user the browser user
     *
     */
    protected static function _makeNewAssignment($currentAssignment, $allowedProperties, $promotion, $atomEntityId, $user){
        if($currentAssignment && !$currentAssignment->task_end) {
            $currentAssignment->task_end = DB::raw('CURRENT_TIMESTAMP');
            $currentAssignment->save();
        }

        $assignment = new Assignment();
        foreach($allowedProperties as $allowed) {
            if(array_key_exists($allowed, $promotion)) {
                $assignment->$allowed = $promotion[$allowed];
            }
        }
        $assignment->created_by = $user->id;
        $assignment->task_id = $promotion['task_id'];
        $assignment->user_id = $promotion['user_id'];
        $assignment->atom_entity_id = $atomEntityId;

        //check if there's already an existing open assignment
        $existing_assignment = Assignment::where('atom_entity_id', '=', $atomEntityId)
            ->where('task_id', '=', $promotion['task_id'])
            ->where('user_id', '=', $promotion['user_id'])
            ->whereNull('task_end')
            ->get()
            ->last();

        if (is_null($existing_assignment)){
             $assignment->save();
        }
    }

    /**
     * check if there's still other assignment with the same task_id (parallelAssignment) existing
     *
     * @param object $assignment The assignment we are going to check against
     * @param integer $productId Limit query to this product
     */
    protected static function _parallelAssignment($assignment, $productId){
        $parallelAssignment = self::allForProduct($productId)
                ->orderBy('assignments.id', 'DESC')
                ->where('assignments.atom_entity_id', '=', $assignment->atom_entity_id)
                ->where('assignments.user_id', '!=', $assignment->user_id)
                ->where('assignments.task_id', '=', $assignment->task_id)
                ->whereNull('assignments.task_end')
                ->groupBy('assignments.id')
                ->limit(1)
                ->first();

        return $parallelAssignment ? $parallelAssignment : null;
    }

    /**
     * change assignment owner
     *
     * @param string $atomEntityId the entity_id of the atom
     * @param interger $productId Limit query to this product
     * @param mixed[] $promotion The promotion we're going to perform
     */
     protected static function _changeAssignmentOwner($atomEntityId, $productId, $promotion) {
        $user = \Auth::user();
        if (array_key_exists('assignment_ids', $promotion)){ //the request is from mass assignment
            foreach ($promotion['assignment_ids'] as $assignmentId){
                $assignment = self::getAssignment($assignmentId, $productId);
                if($assignment['maxId']){
                    unset($assignment['maxId']);
                }
                if($assignment && !$assignment->task_end) {
                    $newAssignment = $assignment->replicate();
                    $newAssignment->created_by = $user->id;
                    $newAssignment->user_id = $promotion['user_id'];
                    $newAssignment->task_end = NULL;

                    //check if this assignment is existing
                    $existing_assignment = Assignment::where('atom_entity_id', '=', $atomEntityId)
                                            ->where('task_id', '=', $assignment->task_id)
                                            ->where('user_id', '=', $promotion['user_id'])
                                            ->whereNull('task_end')
                                            ->get()
                                            ->last();
                    if (is_null($existing_assignment)){
                        $assignment->task_end = DB::raw('CURRENT_TIMESTAMP');
                        $assignment->save();
                        $newAssignment->save();
                    }
                }
            }
        }
        else{
            $assignment = self::getCurrentAssignment($atomEntityId, $productId);
            if($assignment) {
                if($assignment['maxId']){
                    unset($assignment['maxId']);
                }
                //self::_endCurrentAssignment($atomEntityId, $productId);
                $assignment = $assignment->replicate();
                $assignment->created_by = $user->id;
                $assignment->user_id = $promotion['user_id'];

                //check if this assignment is existing
                $existing_assignment = Assignment::where('atom_entity_id', '=', $atomEntityId)
                    ->where('task_id', '=', $assignment->task_id)
                    ->where('user_id', '=', $promotion['user_id'])
                    ->whereNull('task_end')
                    ->get()
                    ->last();
                if (is_null($existing_assignment)){
                    $assignment->save();
                }
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
        /*$query = Assignment::allForProduct($productId)
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

        return null;*/

        //find current assignment
        $query = Assignment::where('user_id' , '=', $userId)
                ->where('atom_entity_id', '=', $atomEntityId)
                ->whereNull('task_end');
        $assignment = $query->get()->last();

        if ($assignment){
            $currentAssignmentId = $assignment['id'];
        }
        else{
            $query = Assignment::where('user_id' , '=', $userId)
                ->where('atom_entity_id', '=', $atomEntityId)
                ->orderBy('assignments.id', 'DESC')
                ->take(1);
            $assignment = $query->get()->last();
            $currentAssignmentId = $assignment['id'];
        }

        //find the next open assignment
        $nextQuery = Assignment::allForProduct($productId)
                ->where('assignments.user_id' , '=', $userId)
                ->whereNull('task_end')
                ->where('assignments.id', '>', $currentAssignmentId)
                ->groupBy('assignments.id')
                ->orderBy('assignments.id', 'ASC')
                ->take(1);
        $nextAssignment = $nextQuery->get()->last();

        if ($nextAssignment){
            return new ApiPayload($nextAssignment);
        }else{ //if nextAssignment is the last one, then start with the first one again
            $firstQuery = Assignment::allForProduct($productId)
                ->where('assignments.user_id' , '=', $userId)
                ->whereNull('assignments.task_end')
                ->where('assignments.id', '!=',$currentAssignmentId)
                ->groupBy('assignments.id')
                ->orderby('assignments.id')
                ->take(1);
            $firstAssignment = $firstQuery->get()->last();
            if ($firstAssignment){
                return new ApiPayload($firstAssignment);
            }
            return null;
        }
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

    public static function getByProductIdForMolecule($productId, $moleculeCode) {
        return self::select('assignments.*')
                ->join('atoms', 'assignments.atom_entity_id', '=', 'atoms.entity_id')
                ->whereIn('atoms.id', function ($q) use ($productId, $moleculeCode) {
                    Atom::buildLatestIDQuery(null, $q);
                    $q->where('molecule_code', '=', $moleculeCode);
                })
                ->orderBy('assignments.id', 'ASC');
    }
}
