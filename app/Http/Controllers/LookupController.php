<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Group;
use App\Boilerplate;
use App\Molecule;
use App\Status;
use App\Task;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller exists to serve up the lookup tables.
 * All endpoint methods should return an ApiPayload or Response.
 */
class LookupController extends Controller
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
            'groups' => Group::all(),
            'boilerplates' => Boilerplate::all(),
            'molecules' => Molecule::all(),
            'tasks' => Task::all(),
            'statuses' => Status::orderBy('id')->get()
        ]);
    }
}
