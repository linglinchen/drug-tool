<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\User;
use App\Group;
use App\UserProduct;
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
     * Create a user.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function postAction($productId, Request $request) {
        $input = $request->all();
        $authUser = \Auth::user();
        $acl = $authUser->ACL;

        $userGroup = isset($input['new_group_id']) ? Group::get($input['new_group_id'], $productId) : null;
        $userLevel = $userGroup ? $userGroup->level : -1;
        $authUserGroup = $authUser->getGroup($productId);
        $authUserLevel = $authUserGroup ? $authUserGroup->level : -1;
        if(!$acl->can('manage_users') || $userLevel >= $authUserLevel) {
            return ApiError::buildResponse(Response::HTTP_FORBIDDEN, 'You do not have permission to modify this user.');
        }

        $user = isset($input['email']) ? User::where('email', '=', $input['email'])->first() : null;
        if($user) {
            return ApiError::buildResponse(Response::HTTP_CONFLICT, 'That user already exists.');
        }

        try {
            $newUser = new User();
            foreach(User::$editableFields as $field) {
                if(isset($input[$field])) {
                    $newUser->$field = $input[$field];
                }
            }
            $newUser->validate();
            $newUser->username = $newUser->email;
            $newUser->save();

            if(isset($input['new_group_id'])) {
                $groupId = $input['new_group_id'];
                $newGroup = Group::get($groupId, $productId);
                $newLevel = Group::getLevel($groupId, $productId);
                if($newGroup && $newLevel < $authUserLevel) {
                    $userProduct = new UserProduct();
                    $userProduct->product_id = $productId;
                    $userProduct->user_id = $newUser->id;
                    $userProduct->group_id = $input['new_group_id'];
                    $userProduct->save();
                }
            }
            $newUser->userProducts();
        }
        catch(\Exception $e) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return new ApiPayload($newUser);
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
                if(!isset($input[$field]) || !($editingSelf || in_array($field, User::$adminModifiableFields))) {
                    continue;
                }

                $user->$field = $input[$field];
            }
            $user->validate();

            if(!$editingSelf && isset($input['new_group_id'])) {
                $groupId = $input['new_group_id'];
                $newGroup = Group::get($groupId, $productId);
                $newLevel = Group::getLevel($groupId, $productId);
                if($newGroup && $newLevel < $authUserLevel) {
                    foreach($user->userProducts as $userProduct) {
                        if($userProduct->product_id == $productId) {
                            if(!$groupId) {
                                $userProduct->delete();
                            }
                            else {
                                $userProduct->group_id = $groupId;
                                $userProduct->save();
                            }
                            break;
                        }
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

        return new ApiPayload($user);
    }

    /**
     * Delete / deactivate a user.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param integer $id The user's ID
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function deleteAction($id, Request $request) {
        $user = User::find($id);

        if(!$user) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested user could not be found.');
        }

        $user->firstname = 'Deactivated';
        $user->lastname = 'User ' . $user->id;
        $user->email = 'deactivated_' . $user->id . '@metis.com';
        $user->password = '';       //killing the password prevents logins
        $user->save();

        return new ApiPayload();
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
