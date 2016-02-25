<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Atom;
use App\Comment;

class AtomCommentController extends Controller
{
    public function post($atomId) {
        if(!Atom::findNewestIfNotDeleted($atomId)) {
            //TODO: Atom not found
        }

        if(!isset($_POST['parentId'])) {
            //TODO: throw exception
        }

        if(!isset($_POST['text'])) {
            //TODO: throw exception
        }

        $comment = Comment::create([
            'atomId' => $atomId,
            'userId' => 1,        //TODO: make this use the user's actual id
            'parentId' => (int)$_POST['parentId'],
            'text' => $_POST['text'],
            'deleted' => false
        ]);

        return $comment;
    }

    public function delete($atomId, $commentId) {
        if(!Atom::findNewestIfNotDeleted($atomId)) {
            //TODO: Atom not found
        }

        $conditions = [
                ['id', '=', $commentId],
                ['atomId', '=', $atomId]
            ];
        $comment = Comment::where($conditions)->first();

        if(!$comment) {
            //TODO: comment not found
        }

        $comment->deleted = true;
        $comment->save();

        return $comment;
    }
}
