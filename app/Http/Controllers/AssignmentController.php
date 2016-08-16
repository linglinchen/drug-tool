<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Assignment;

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

        //get the currently active assignment
        $lastAssignment = Assignment::find(1)
                ->orderBy('id', 'DESC')
                ->where('active', '=', '1')
                ->where('atomEntityId', '=', $assignment->atomEntityId)
                ->first();

        //save the new assignment
        $assignment->save();

        //end the previous assignment
        if($lastAssignment) {
            $lastAssignment->taskEnd = $assignment->created_at;
            $lastAssignment->save();
        }

        return new ApiPayload($assignment);
    }
}
