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


Route::group(['prefix' => 'api'], function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::get('atom', ['uses' => 'AtomController@list']);
        Route::post('atom', ['uses' => 'AtomController@post']);
        Route::get('atom/{atomId}', ['uses' => 'AtomController@get']);
        Route::put('atom/{atomId}', ['uses' => 'AtomController@put']);
        Route::delete('atom/{atomId}', ['uses' => 'AtomController@delete']);

        Route::post('atom/{atomId}/comment', ['uses' => 'AtomCommentController@post']);
        Route::delete('atom/{atomId}/comment/{commentId}', ['uses' => 'AtomCommentController@delete']);
    });
});
