<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use DB;

use App\Atom;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller is here to promote atoms.
 * All endpoint methods should return an ApiPayload or Response.
 */
class AtomPromotionController extends Controller {
    /**
     * Promote atom(s) in the workflow. Outputs an array of touched atoms with their updated assignments.
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

        try {
            $assignments = Atom::promote($atomEntityIds, $promotion);
        }
        catch(\Exception $e) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return new ApiPayload($assignments);
    }
}
