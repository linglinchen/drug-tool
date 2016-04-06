<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;

class UserController extends Controller
{
    public function loginAction() {
    	return \Auth::user();
    }

    public function logoutAction() {
        //
    }
}
