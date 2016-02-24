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
        Route::get('atom', ['uses' => 'AtomComment@list']);
        Route::post('atom', ['uses' => 'AtomComment@post']);
        Route::get('atom/{id}', ['uses' => 'AtomComment@get']);
        Route::put('atom/{id}', ['uses' => 'AtomComment@put']);
        Route::delete('atom/{id}', ['uses' => 'AtomComment@delete']);

        Route::post('atom/{atomId}/comment', ['uses' => 'AtomComment@post']);
        Route::delete('atom/{atomId}/comment/{commentId}', ['uses' => 'AtomComment@delete']);
    });
});
