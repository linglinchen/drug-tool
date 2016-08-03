<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Molecule;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller handles molecules.
 * All endpoint methods should return an ApiPayload or Response.
 */
class MoleculeController extends Controller
{
    /**
     * GET a list of all molecules.
     *
     * @api
     *
     * @return ApiPayload|Response
     */
    public function listAction() {
        return new ApiPayload(Molecule::all());
    }
}
