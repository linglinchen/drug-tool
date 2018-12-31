<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\User;
use App\Group;
use App\Product;
use App\UserProduct;
use App\AccessControl;
use App\LoginHistory;
use App\AdminLog;

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
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function listAction($productId, Request $request) {
        $input = $request->all();

        $authUser = \Auth::user();
        if($authUser->isAdminAnywhere()) {
            AdminLog::write('Admin ' . $authUser->id . ' retrieved the user listing for product ' . $productId);
        }

        $includeOrphaned = self::_includeOrphaned($request);

        return new ApiPayload(User::publicList($productId, $includeOrphaned));
    }

    /**
     * GET a user by ID.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param integer $id The user's ID
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function getAction($productId, $id, Request $request) {
        $authUser = \Auth::user();
        $includeOrphaned = self::_includeOrphaned($request);
        $user = User::get($id, $productId, $includeOrphaned);

        if(!$user) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested user could not be found.');
        }

        if($authUser->isAdminAnywhere()) {
            AdminLog::write('Admin ' . $authUser->id . ' viewed user ' . $user->id);

            $user->assignments;
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

        AdminLog::write('Admin ' . $authUser->id . ' created user ' . $newUser->id);

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
    public function loginAction() {
        $user = \Auth::user();

        $userId = $user->id;

        $timestamp = (new LoginHistory())->freshTimestampString();

        $newLoginHistory = new LoginHistory();
        $newLoginHistory->user_id = $user->id;
        $newLoginHistory->login_time = $timestamp;
        $newLoginHistory->success = 'yes';
        $newLoginHistory->save();

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

    /**
     * Request a password reset.
     *
     * @api
     *
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function requestResetAction(Request $request) {
        $email = $request->input('email');
        $user = User::getByEmail($email);

        if($user) {     //fail silently, so attackers don't get extra info
            $user->setResetToken()->sendResetEmail();
        }

        return new ApiPayload([
            'success' => true
        ]);
    }

    /**
     * Reset a user's password.
     *
     * @api
     *
     * @param string $token The user's reset token
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function resetAction($token, Request $request) {
        $password = $request->input('password');
        $user = User::getByToken($token);

        if(!$user) {
            return ApiError::buildResponse(
                Response::HTTP_NOT_FOUND,
                'Your token expired. Please request another password reset, and try again.'
            );
        }

        try {
            User::validatePassword($password);
            $user->new_password = $password;
            $user->save();
            $user->destroyResetToken();
        }
        catch(\Exception $e) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return new ApiPayload([
            'success' => true
        ]);
    }

    public function putAction($productId, $id, Request $request) {
        $input = $request->all();

        $authUser = \Auth::user();
        $authUserGroup = $authUser->getGroup($productId);
        $authUserLevel = $authUserGroup ? $authUserGroup->level : -1;

        $user = User::get($id, $productId);
        $editingSelf = $user->id == $authUser->id;

        if(!$user) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested user could not be found.');
        }

        if(!$user->active) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'That user is deactivated.');
        }

        if(!$authUser->canModify($user, $productId)) {
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

        AdminLog::write('Admin ' . $authUser->id . ' modified user ' . $user->id);

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
    public function deleteAction($productId, $id, Request $request) {
        $authUser = \Auth::user();
        $user = User::find($id);

        if(!$user) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested user could not be found.');
        }

        if(!$authUser->canModify($user)) {
            return ApiError::buildResponse(Response::HTTP_FORBIDDEN, 'You do not have permission to modify this user.');
        }

        $user->firstname = 'Deactivated';
        $user->lastname = 'User ' . $user->id;
        $user->email = 'deactivated_' . $user->id . '@metis.com';
        $user->password = '';       //killing the password prevents logins
        $user->active = false;      //just to be sure... and to show a deactivation indicator in the ui
        $user->save();

        AdminLog::write('Admin ' . $authUser->id . ' deactivated user ' . $user->id);

        return new ApiPayload();
    }

    /**
     * GET a list of all products that the specified user has open assignments in.
     *
     * @api
     *
     * @param integer $id The user's ID
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function getProductswithOpenAssignmentsAction($id, Request $request) {
        $user = User::find($id);
        if(!$user) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested user could not be found.');
        }

        return new ApiPayload(Product::withOpenAssignments($id));
    }

    /**
     * Should we allow orphaned users to be returned?
     *
     * @param Request $request The Laravel Request object
     *
     * @return boolean Include orphaned users?
     */
    protected static function _includeOrphaned($request) {
        $input = $request->all();

        return isset($input['include_orphaned']) && (
            strtolower($input['include_orphaned']) === 'true' ||
            $input['include_orphaned'] == 1
        );
    }
}
