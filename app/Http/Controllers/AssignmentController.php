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
class AssignmentController extends Controller
{
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
        return new ApiPayload(Assignment::getList($request->input('filters'), $request->input('order'), true));
    }

    /**
     * POST a new assignment.
     *
     * @api
     *
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function postAction(Request $request) {
        //build the new assignment
        $input = $request->all();
        $user = \Auth::user();
        $assignment = new Assignment();
        foreach($this->_allowedProperties as $allowed) {
            if(array_key_exists($allowed, $input)) {
                $assignment->$allowed = $input[$allowed];
            }
        }
        $assignment->createdBy = $user->id;

        $currentAssignment = Assignment::getCurrentAssignment($assignment->atomEntityId);

        //save the new assignment if it has a taskId
        if($assignment->taskId) {
            $assignment->save();
        }

        //end the previous assignment
        if($currentAssignment) {
            $currentAssignment->taskEnd = DB::raw('CURRENT_TIMESTAMP');
            $currentAssignment->save();
        }

        return new ApiPayload(Atom::getAssignments($assignment->atomEntityId));
    }
}
