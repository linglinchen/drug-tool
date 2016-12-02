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
	/*
	public function loginAction() {
		
		
		$user = \Auth::user();
		$accessControl = new AccessControl();
		$permissions = $accessControl->loadPermissions($user);

	
		return new ApiPayload([
			'user'			=> $user,
			'permissions'	=> $permissions
		]);
		
		
	}
	*/
	public function loginAction() {
		
		 
		//print $user= \Auth::attempt($credentials);
		//\Log::info("Logging one variable: " . print_r($user));
		 if (\Auth::attempt($credentials) {

        // Returns \App\User model configured in `config/auth.php`.
        $user = \Auth::user();

        return redirect()->to('home')
            ->withMessage('Logged in!');
    	}

    	return redirect()->to('login')
       		->withMessage('Hmm... Your username or password is incorrect');
		
		
	}

	/**
	 * This is just an unused stub.
	 */
	public function logoutAction() {
		//
	}
}
