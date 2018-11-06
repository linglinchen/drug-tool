<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\User;
use App\AccessControl;
use App\LoginHistory;

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
     * @return ApiPayload|Response
	 */
	public function listAction() {
		return new ApiPayload(User::publicList());
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
		
		$userId = $user->id;

		$timestamp = (new LoginHistory())->freshTimestampString();

		$newLoginHistory = new LoginHistory();
		$newLoginHistory->user_id = $user->id;
		$newLoginHistory->login_time = $timestamp;
		$newLoginHistory->success = 'yes';
		$newLoginHistory->save();

		return new ApiPayload([
			'user'			=> $user,
			'permissions'	=> $user->ACL->permissions
		]);
	}

	/**
	 * This is just an unused stub.
	 */
	public function logoutAction() {
		//
	}
}
