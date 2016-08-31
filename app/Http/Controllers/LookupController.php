<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Group;
use App\Boilerplate;
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
            'tasks' => Task::all(),
            'statuses' => Status::orderBy('id')->get()
        ]);
    }
}
