<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Boilerplate;
use App\Molecule;
use App\Status;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller exists to serve up the lookup tables.
 * All endpoint methods should return an ApiPayload or Response.
 */
class LookupsController extends Controller
{
    /**
     * GET a list of all statuses.
     *
     * @api
     *
     * @return ApiPayload|Response
     */
    public function listAction() {
        return new ApiPayload([
            'boilerplates' => Boilerplate::all(),
            'molecules' => Molecule::all(),
            'statuses' => Status::orderBy('id')->get()
        ]);
    }
}
