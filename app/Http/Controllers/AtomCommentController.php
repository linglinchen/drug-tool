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
            'text' => $_POST['text']
        ]);

        return $comment;
    }

    public function delete($atomId, $commentId) {
        if(!Atom::findNewestIfNotDeleted($atomId)) {
            //TODO: Atom not found
        }

        $comment = Comment::where([
                ['id', '=', $commentId],
                ['atomId', '=', $atomId]
            ])
        	->first();

        if(!$comment) {
            //TODO: comment not found
        }

        $comment->delete();

        return $comment;
    }
}
