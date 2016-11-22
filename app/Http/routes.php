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
            Route::get('{productId}/atom', ['uses' => 'AtomController@listAction']);
            Route::post('{productId}/atom', ['uses' => 'AtomController@postAction']);
            Route::get('{productId}/atom/search', ['uses' => 'AtomController@searchAction']);
            Route::put('{productId}/atom/massUpdate', ['uses' => 'AtomController@massUpdateAction']);
            Route::get('{productId}/atom/{entityId}', ['uses' => 'AtomController@getAction']);
            Route::get('{productId}/atom/{entityId}/history', ['uses' => 'AtomController@historyAction']);
            Route::get('{productId}/atom/{entityId}/version/{id}', ['uses' => 'AtomController@getAction']);
            Route::put('{productId}/atom/{entityId}', ['uses' => 'AtomController@putAction']);
            Route::delete('{productId}/atom/{entityId}', ['uses' => 'AtomController@deleteAction']);

            Route::post('{productId}/atom/promote', ['uses' => 'AtomPromotionController@postAction']);

            Route::get('{productId}/atom/{entityId}/comment', ['uses' => 'AtomCommentController@getAction']);
            Route::post('{productId}/atom/{entityId}/comment', ['uses' => 'AtomCommentController@postAction']);
            Route::delete('{productId}/atom/{entityId}/comment/{commentId}', ['uses' => 'AtomCommentController@deleteAction']);

            Route::get('{productId}/molecule', ['uses' => 'MoleculeController@listAction']);
            Route::get('{productId}/molecule/{code}', ['uses' => 'MoleculeController@getAction']);
            Route::get('{productId}/molecule/{code}/export', ['uses' => 'MoleculeExportController@getAction']);
            Route::put('{productId}/molecule/{code}/sort', ['uses' => 'MoleculeSortController@putAction']);
            Route::get('{productId}/molecule/{code}/lock', ['uses' => 'MoleculeLockController@lockAction']);
            Route::get('{productId}/molecule/{code}/unlock', ['uses' => 'MoleculeLockController@unlockAction']);

            Route::get('lookup', ['uses' => 'LookupController@listAction']);

            Route::get('{productId}/assignment', ['uses' => 'AssignmentController@listAction']);
            Route::post('{productId}/assignment', ['uses' => 'AssignmentController@postAction']);
            Route::get('{productId}/assignment/{atomEntityId}/next', ['uses' => 'AssignmentController@nextAction']);

            Route::get('user', ['uses' => 'UserController@listAction']);
            Route::post('user/login', ['uses' => 'UserController@loginAction']);
            Route::post('user/logout', ['uses' => 'UserController@logoutAction']);

            Route::get('{productId}/report', ['uses' => 'ReportController@listAction']);
            Route::get('{productId}/report/discontinued', ['uses' => 'ReportController@discontinuedAction']);
            Route::get('{productId}/report/statuses', ['uses' => 'ReportController@statusesAction']);
            Route::get('{productId}/report/edits', ['uses' => 'ReportController@editsAction']);
            Route::get('{productId}/report/openAssignments', ['uses' => 'ReportController@openAssignmentsAction']);
            Route::get('{productId}/report/brokenLinks', ['uses' => 'ReportController@brokenLinksAction']);
            Route::get('{productId}/report/comments', ['uses' => 'ReportController@commentsAction']);
            Route::get('{productId}/report/moleculeStats', ['uses' => 'ReportController@moleculeStatsAction']);
        });
    });
});
