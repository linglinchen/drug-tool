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
            Route::get('logout', ['uses' => 'UserController@logoutAction']);
        });

        Route::group(['middleware' => 'auth.api'], function () {		//secured endpoints
            Route::get('atom', ['uses' => 'AtomController@listAction']);
            Route::post('atom', ['uses' => 'AtomController@postAction']);
            Route::get('atom/search', ['uses' => 'AtomController@searchAction']);
            Route::put('atom/massUpdate', ['uses' => 'AtomController@massUpdateAction']);
            Route::get('atom/{entityId}', ['uses' => 'AtomController@getAction']);
            Route::get('atom/{entityId}/history', ['uses' => 'AtomController@historyAction']);
            Route::get('atom/{entityId}/version/{id}', ['uses' => 'AtomController@getAction']);
            Route::put('atom/{entityId}', ['uses' => 'AtomController@putAction']);
            Route::delete('atom/{entityId}', ['uses' => 'AtomController@deleteAction']);

            Route::post('atom/promote', ['uses' => 'AtomPromotionController@postAction']);

            Route::get('atom/{entityId}/comment', ['uses' => 'AtomCommentController@getAction']);
            Route::post('atom/{entityId}/comment', ['uses' => 'AtomCommentController@postAction']);
            Route::delete('atom/{entityId}/comment/{commentId}', ['uses' => 'AtomCommentController@deleteAction']);

            Route::get('molecule', ['uses' => 'MoleculeController@listAction']);
            Route::get('molecule/{code}', ['uses' => 'MoleculeController@getAction']);
            Route::get('molecule/{code}/export', ['uses' => 'MoleculeExportController@getAction']);
            Route::put('molecule/{code}/sort', ['uses' => 'MoleculeSortController@putAction']);
            Route::get('molecule/{code}/lock', ['uses' => 'MoleculeLockController@lockAction']);
            Route::get('molecule/{code}/unlock', ['uses' => 'MoleculeLockController@unlockAction']);

            Route::get('lookup', ['uses' => 'LookupController@listAction']);

            Route::get('assignment', ['uses' => 'AssignmentController@listAction']);
            Route::post('assignment', ['uses' => 'AssignmentController@postAction']);
            Route::get('assignment/{atomEntityId}/next', ['uses' => 'AssignmentController@nextAction']);

            Route::get('user', ['uses' => 'UserController@listAction']);
            Route::post('user/login', ['uses' => 'UserController@loginAction']);
            Route::post('user/logout', ['uses' => 'UserController@logoutAction']);

            Route::get('report', ['uses' => 'ReportController@listAction']);
            Route::get('report/discontinued', ['uses' => 'ReportController@discontinuedAction']);
            Route::get('report/statuses', ['uses' => 'ReportController@statusesAction']);
            Route::get('report/edits', ['uses' => 'ReportController@editsAction']);
            Route::get('report/openAssignments', ['uses' => 'ReportController@openAssignmentsAction']);
            Route::get('report/brokenLinks', ['uses' => 'ReportController@brokenLinksAction']);
            Route::get('report/comments', ['uses' => 'ReportController@commentsAction']);
            Route::get('report/moleculeStats', ['uses' => 'ReportController@moleculeStatsAction']);
        });
    });
});
