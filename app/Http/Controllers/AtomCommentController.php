<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\ApiError;
use App\ApiPayload;
use App\Atom;
use App\Comment;

class AtomCommentController extends Controller
{
    public function post($atomId) {
        if(!Atom::findNewestIfNotDeleted($atomId)) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found. It might have been deleted.');
        }

        if(!isset($_POST['parentId'])) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'Missing parentId field.');
        }

        if(!isset($_POST['text'])) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'Missing text field.');
        }

        $comment = Comment::create([
            'atomId' => $atomId,
            'userId' => 1,        //TODO: make this use the user's actual id
            'parentId' => (int)$_POST['parentId'],
            'text' => $_POST['text']
        ]);

        return new ApiPayload($comment);
    }

    public function delete($atomId, $commentId) {
        if(!Atom::findNewestIfNotDeleted($atomId)) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found. It might have been deleted.');
        }

        $comment = Comment::where([
                ['id', '=', $commentId],
                ['atomId', '=', $atomId]
            ])
        	->first();

        if(!$comment) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested comment could not be found. It might have been deleted.');
        }

        $comment->delete();

        return new ApiPayload($comment);
    }
}
