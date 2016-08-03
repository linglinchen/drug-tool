<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\AccessControl;

/**
 * This controller handles users.
 * All endpoint methods should return an ApiPayload or Response.
 */
class UserController extends Controller
{
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
		$accessControl = new AccessControl();
		$permissions = $accessControl->loadPermissions($user);

		return new ApiPayload([
			'user'			=> $user,
			'permissions'	=> $permissions
		]);
	}

	/**
	 * This is just an unused stub.
	 */
	public function logoutAction() {
		//
	}
}
