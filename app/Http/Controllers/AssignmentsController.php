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
class AssignmentsController extends Controller
{
    /**
     * GET a list of assignments.
     *
     * @api
     *
     * @return ApiPayload|Response
     */
    public function listAction() {
        return new ApiPayload(Assignment::all());
    }
}
