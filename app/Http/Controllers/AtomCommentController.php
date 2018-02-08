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
    public function getAction($productId, $atomEntityId) {
        if(!Atom::findNewestIfNotDeleted($atomEntityId, $productId)) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found. It might have been deleted.');
        }

        return new ApiPayload(Comment::getByAtomEntityId($atomEntityId, $productId));
    }

    public function postAction($productId, $atomEntityId, Request $request) {
        if(!Atom::findNewestIfNotDeleted($atomEntityId, $productId)) {
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

        return new ApiPayload(Comment::getByAtomEntityId($atomEntityId, $productId));
    }
    public function updateAction($productId, $atomEntityId, $commentId, Request $request) {

        if(!Atom::findNewestIfNotDeleted($atomEntityId, $productId)) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found. It might have been deleted.');
        }

        $text = $request->input('text');
        print_r($text);
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

        return new ApiPayload(Comment::getByAtomEntityId($atomEntityId, $productId));
    }



/*    public function updateAction($productId, $atomEntityId, Request $request) {
        if(!Atom::findNewestIfNotDeleted($atomEntityId, $productId)) {
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

        return new ApiPayload(Comment::getByAtomEntityId($atomEntityId, $productId));
    }*/

    public function deleteAction($productId, $atomEntityId, $commentId) {
        if(!Atom::findNewestIfNotDeleted($atomEntityId, $productId)) {
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
