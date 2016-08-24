<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use DB;

use App\Assignment;
use App\Atom;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller exists to serve up the lookup tables.
 * All endpoint methods should return an ApiPayload or Response.
 */
class AssignmentController extends Controller {
    protected $_allowedProperties = ['atomEntityId', 'userId', 'taskId', 'taskEnd'];

    /**
     * GET a list of assignments.
     *
     * @api
     *
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function listAction(Request $request) {
        $limit = $request->input('limit') ? max((int)$request->input('limit', 10), 1) : null;
        $page = $request->input('page') ? max((int)$request->input('page', 1), 1) : null;

        $payload = (new Assignment)->getList($request->input('filters'), $request->input('order'), $limit, $page, true);

        return new ApiPayload($payload);
    }

    /**
     * Promote atom(s) in the workflow.
     *
     * @api
     *
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function postAction(Request $request) {
        $atomEntityIds = $request->input('atomEntityIds');
        $atomEntityIds = is_string($atomEntityIds) ? [$atomEntityIds] : $atomEntityIds;
        $promotion = $request->input('promotion');

        $user = \Auth::user();

        //build the new assignments
        $atoms = [];
        foreach($atomEntityIds as $atomEntityId) {
            $atom = Atom::findNewestIfNotDeleted($atomEntityId);
            if($atom) {
                continue;       //skip atoms that don't exist
            }

            //save a new assignment if we have a taskId
            if(isset($promotion['taskId'])) {
                Assignment::promote($atomEntityId, $promotion);
            }

            //we might need to update the atom
            if(isset($promotion['statusId'])) {
                $atom->statusId = $promotion['statusId'];
                $atom->save();
            }

            $atom = $atom->addAssignments()->toArray();
            unset($atom['xml']);
            $atoms[] = $atom;
        }

        return new ApiPayload($atoms);
    }

    /**
     * GET the user's next open assignment.
     *
     * @api
     *
     * @param integer $assignmentId The current assignment's ID
     *
     * @return ApiPayload|Response
     */
    public function nextAction($assignmentId) {
        $user = \Auth::user();

        $assignment = Assignment::where('userId' , '=', $user->id)
                ->whereNull('taskEnd')
                ->where('id', '>', $assignmentId)
                ->first();

        return new ApiPayload($assignment);
    }
}
