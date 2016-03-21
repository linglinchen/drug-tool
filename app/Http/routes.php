<?php

/*
|--------------------------------------------------------------------------
| Routes File
|--------------------------------------------------------------------------
|
| Here is where you will register all of the routes in an application.
| It's a breeze. Simply tell Laravel the URIs it should respond to
| and give it the controller to call when that URI is requested.
|
*/

Route::get('/', function () {
    return view('welcome');
});

/*
|--------------------------------------------------------------------------
| Application Routes
|--------------------------------------------------------------------------
|
| This route group applies the "web" middleware group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});


Route::group(['domain' => env('API_DOMAIN')], function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::group([], function () {		//unsecured endpoints
        });

        Route::group(['middleware' => 'oauth'], function () {		//secured endpoints
            Route::get('atom', ['uses' => 'AtomController@listAction']);
            Route::post('atom', ['uses' => 'AtomController@postAction']);
            Route::get('atom/{atomId}', ['uses' => 'AtomController@geAction']);
            Route::put('atom/{atomId}', ['uses' => 'AtomController@putAction']);
            Route::delete('atom/{atomId}', ['uses' => 'AtomController@deleteAction']);

            Route::post('atom/{atomId}/comment', ['uses' => 'AtomCommentController@postAction']);
            Route::delete('atom/{atomId}/comment/{commentId}', ['uses' => 'AtomCommentController@deleteAction']);

            Route::post('login', ['uses' => 'UserController@loginAction']);
            Route::get('logout', ['uses' => 'UserController@logoutAction']);
        });
    });
});
