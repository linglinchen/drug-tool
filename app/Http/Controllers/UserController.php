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
     * Update a user.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param integer $id The user's ID
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function putAction($productId, $id, Request $request) {
        $user = User::get($id, $productId);

        if(!$user) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested user could not be found.');
        }

        $input = $request->all();
        $authUser = \Auth::user();
        $acl = $authUser->ACL;
        $editingSelf = $user->id == $authUser->id;

        $userGroup = $user->getGroup($productId);
        $userLevel = $userGroup ? $userGroup->level : -1;
        $authUserGroup = $authUser->getGroup($productId);
        $authUserLevel = $authUserGroup ? $authUserGroup->level : -1;
        if(!$editingSelf && !($acl->can('manage_users') && $userLevel < $authUserLevel)) {
            return ApiError::buildResponse(Response::HTTP_FORBIDDEN, 'You do not have permission to modify this user.');
        }

        try {
            foreach(User::$editableFields as $field) {
                if(!isset($input[$field]) || !($editingSelf || in_array($field, User::$adminEditableFields))) {
                    continue;
                }

                $user->$field = $input[$field];
            }
            $user->validate();

            if(!$editingSelf && isset($input['groupId'])) {
                $groupId = $input['groupId'];
                foreach($user->userProducts as $userProduct) {
                    if($userProduct->id == $groupId && $userProduct->productId == $productId) {
                        if(!$groupId) {
                            $userProduct->delete();
                        }
                        else {
                            $userProduct->groupId = $groupId;
                            $userProduct->save();
                        }
                        break;
                    }
                }
            }

            $user->save();

            $refreshedUser = User::get($id, $productId);
            $refreshedUser->userProducts;
        }
        catch(\Exception $e) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return new ApiPayload($refreshedUser);
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
            'user'          => $user,
            'permissions'   => $user->ACL->permissions
        ]);
    }

    /**
     * This is just an unused stub.
     */
    public function logoutAction() {
        //
    }
}
