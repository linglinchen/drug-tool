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
| This route group applies the "web" middlewarere group to every route
| it contains. The "web" middleware group is defined in your HTTP
| kernel and includes session state, CSRF protection, and more.
|
*/

Route::group(['middleware' => ['web']], function () {
    //
});


Route::group(['domain' => env('API_DOMAIN')], function () {
    Route::group(['prefix' => 'v1'], function () {
        Route::group([], function () {      //unsecured endpoints
            Route::get('user/logout', ['uses' => 'UserController@logoutAction']);
            Route::post('user/requestReset', ['uses' => 'UserController@requestResetAction']);
        });

        Route::group(['middleware' => 'auth.api'], function () {        //secured endpoints
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
            Route::post('{productId}/atom/{entityId}/comment/update/{commentId}', ['uses' => 'AtomCommentController@updateAction']);

            Route::get('{productId}/molecule', ['uses' => 'MoleculeController@listAction']);
            Route::get('{productId}/molecule/{code}', ['uses' => 'MoleculeController@getAction']);
            Route::get('{productId}/molecule/{code}/export', ['uses' => 'MoleculeExportController@getAction']);
            Route::get('{productId}/molecule/{code}/export/count', ['uses' => 'MoleculeExportController@countAction']);
            Route::put('{productId}/molecule/{code}/sort', ['uses' => 'MoleculeSortController@putAction']);
            Route::put('{productId}/molecule/{code}/sort/auto', ['uses' => 'MoleculeSortController@autoAction']);
            Route::get('{productId}/molecule/{code}/lock', ['uses' => 'MoleculeLockController@lockAction']);
            Route::get('{productId}/molecule/{code}/unlock', ['uses' => 'MoleculeLockController@unlockAction']);

            Route::get('{productId}/domain', ['uses' => 'DomainController@listAction']);
            Route::get('{productId}/domain/{code}', ['uses' => 'DomainController@getAction']);

            Route::get('{productId}/lookup', ['uses' => 'LookupController@listAction']);

            Route::get('product', ['uses' => 'ProductController@listAction']);

            Route::get('{productId}/assignment', ['uses' => 'AssignmentController@listAction']);
            Route::post('{productId}/assignment', ['uses' => 'AssignmentController@postAction']);
            Route::get('{productId}/assignment/{atomEntityId}/next', ['uses' => 'AssignmentController@nextAction']);
            Route::get('{productId}/assignment/{atomEntityId}/current', ['uses' => 'AssignmentController@currentAction']);

            Route::get('{productId}/user', ['uses' => 'UserController@listAction']);
            Route::post('{productId}/user', ['uses' => 'UserController@postAction']);
            Route::get('{productId}/user/{id}', ['uses' => 'UserController@getAction']);
            Route::put('{productId}/user/{id}', ['uses' => 'UserController@putAction']);
            Route::delete('{productId}/user/{id}', ['uses' => 'UserController@deleteAction']);
            Route::get('user/{id}/productsWithOpenAssignments', ['uses' => 'UserController@getProductswithOpenAssignmentsAction']);
            Route::post('user/login', ['uses' => 'UserController@loginAction']);
            Route::post('user/logout', ['uses' => 'UserController@logoutAction']);

            Route::get('report', ['uses' => 'ReportController@listAction']);
            Route::get('{productId}/report', ['uses' => 'ReportController@menuAction']);
            Route::get('{productId}/report/discontinued', ['uses' => 'ReportController@discontinuedAction']);
            Route::get('{productId}/report/statuses', ['uses' => 'ReportController@statusesAction']);
            Route::get('{productId}/report/edits', ['uses' => 'ReportController@editsAction']);
            Route::get('{productId}/report/openAssignments', ['uses' => 'ReportController@openAssignmentsAction']);
            Route::get('{productId}/report/brokenLinks', ['uses' => 'ReportController@brokenLinksAction']);
            Route::get('{productId}/report/newFigures', ['uses' => 'ReportController@newFiguresAction']);
            Route::get('{productId}/report/comments', ['uses' => 'ReportController@commentsAction']);
            Route::get('{productId}/report/moleculeStats', ['uses' => 'ReportController@moleculeStatsAction']);
            Route::get('{productId}/report/domainStats', ['uses' => 'ReportController@domainStatsAction']);
            Route::get('{productId}/report/reviewerStats', ['uses' => 'ReportController@reviewerStatsAction']);
            Route::get('{productId}/report/suggestedImageStats', ['uses' => 'ReportController@suggestedImageStatsAction']);
            Route::get('{productId}/report/legacyImageStats', ['uses' => 'ReportController@legacyImageStatsAction']);
        });
    });
});
