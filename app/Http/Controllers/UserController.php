<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\AccessControl;

class UserController extends Controller
{
	public function loginAction() {
		$user = \Auth::user();
		$accessControl = new AccessControl();
		$permissions = $accessControl->loadPermissions($user);

		return new ApiPayload([
			'user'			=> $user,
			'permissions'	=> $permissions
		]);
	}

	public function logoutAction() {
		//
	}
}
