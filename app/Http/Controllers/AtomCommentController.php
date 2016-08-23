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

class AtomCommentController extends Controller {
    public function getAction($atomEntityId) {
        if(!Atom::findNewestIfNotDeleted($atomEntityId)) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found. It might have been deleted.');
        }

        return new ApiPayload(Comment::getByAtomEntityId($atomEntityId));
    }

    public function postAction($atomEntityId, Request $request) {
        if(!Atom::findNewestIfNotDeleted($atomEntityId)) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found. It might have been deleted.');
        }

        $text = $request->input('text');
        $parentId = $request->input('parentId');

        if(!$text) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'Missing text field.');
        }

        $comment = Comment::create([
            'atomEntityId' => $atomEntityId,
            'userId' => \Auth::user()['id'],
            'parentId' => $parentId,
            'text' => $text
        ]);

        return new ApiPayload(Comment::getByAtomEntityId($atomEntityId));
    }

    public function deleteAction($atomEntityId, $commentId) {
        if(!Atom::findNewestIfNotDeleted($atomEntityId)) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found. It might have been deleted.');
        }

        $comment = Comment::where([
                ['id', '=', $commentId],
                ['atomEntityId', '=', $atomEntityId]
            ])
        	->first();

        if(!$comment) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested comment could not be found. It might have been deleted.');
        }

        $comment->delete();

        return new ApiPayload($comment);
    }
}
