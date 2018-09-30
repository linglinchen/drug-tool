<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\User;
use App\AccessControl;

/**
 * This controller handles users.
 * All endpoint methods should return an ApiPayload or Response.
 */
class UserController extends Controller
{
    /**
     * Get a list of users.
     *
     * @api
     *
     * @param integer $productId The current product's id
     *
     * @return ApiPayload|Response
     */
    public function listAction($productId) {
        return new ApiPayload(User::publicList($productId));
    }

    /**
     * GET a user by ID.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param integer $id The user's ID
     *
     * @return ApiPayload|Response
     */
    public function getAction($productId, $id) {
        $user = User::get($id, $productId);

        if(!$user) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested user could not be found.');
        }

        return new ApiPayload($user);
    }

    /**
     * This endpoint's name is misleading. All it does is provide the user's information after they have already
     * passed through the authentication layer.
     *
     * @api
     *
     * @return ApiPayload|Response
     */
    public function loginAction() {
        $user = \Auth::user();

        return new ApiPayload([
            'user'            => $user,
            'permissions'    => $user->ACL->permissions
        ]);
    }

    /**
     * This is just an unused stub.
     */
    public function logoutAction() {
        //
    }
}
