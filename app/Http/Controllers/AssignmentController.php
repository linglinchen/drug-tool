<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use DB;

use App\Assignment;
use App\Atom;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller is used for various aspects of assignment management.
 * All endpoint methods should return an ApiPayload or Response.
 */
class AssignmentController extends Controller {
    protected $_allowedProperties = ['atomEntityId', 'userId', 'taskId', 'taskEnd'];

    /**
     * GET a list of assignments.
     *
     * @api
     *
     * @param integer $productId The current product's ID
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function listAction($productId, Request $request) {
        $limit = $request->input('limit') ? max((int)$request->input('limit', 10), 1) : null;
        $page = $request->input('page') ? max((int)$request->input('page', 1), 1) : null;

        $payload = (new Assignment)->getList($productId, $request->input('filters'), $request->input('order'), $limit, $page, true);

        return new ApiPayload($payload);
    }

    /**
     * GET the user's next open assignment.
     *
     * @api
     *
     * @param integer $productId The current product's ID
     * @param string $atomEntityId The current assignment's atomEntityId
     *
     * @return ApiPayload|Response
     */
    public function nextAction($productId, $atomEntityId) {
        $user = \Auth::user();

        return Assignment::next($user->id, $atomEntityId, $productId);
    }

    /**
     * GET the atom's most recent assignment.
     *
     * @api
     *
     * @param integer $productId The current product's ID
     * @param string $atomEntityId The current assignment's atomEntityId
     *
     * @return ApiPayload|Response
     */
    public function currentAction($productId, $atomEntityId) {
        return Assignment::getCurrentAssignment($atomEntityId, $productId);
    }
}
