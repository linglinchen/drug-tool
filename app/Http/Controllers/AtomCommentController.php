<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
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
        $parentId = $request->input('parent_id');

        if(!$text) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'Missing text field.');
        }

        $comment = Comment::create([
            'atom_entity_id' => $atomEntityId,
            'user_id' => \Auth::user()['id'],
            'parent_id' => $parentId,
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
                ['atom_entity_id', '=', $atomEntityId]
            ])
        	->first();

        if(!$comment) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested comment could not be found. It might have been deleted.');
        }

        $comment->delete();

        return new ApiPayload($comment);
    }
}
