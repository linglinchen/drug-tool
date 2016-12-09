<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Group;
use App\Boilerplate;
use App\Task;
use App\Status;
use App\Product;

use App\ApiError;
use App\ApiPayload;

/**
 * All endpoint methods should return an ApiPayload or Response.
 */
class ProductController extends Controller
{
    /**
     * GET a list of all products.
     *
     * @api
     *
     * @return ApiPayload|Response
     */
    public function listAction() {
        return new ApiPayload(Product::all());
    }
}
