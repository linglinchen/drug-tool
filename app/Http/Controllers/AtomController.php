<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Atom;

use App\ApiError;
use App\ApiPayload;

class AtomController extends Controller
{
    public function listAction() {
        $list = [];
        $atoms = Atom::orderBy('strippedTitle', 'asc')->get();
        foreach($atoms as $atom) {
            $firstChar = strtoupper($atom['strippedTitle'][0]);
            if(!isset($list[$firstChar])) {
                $list[$firstChar] = [];
            }

            $list[$firstChar][] = [
                'id' => $atom->id,
                'title' => $atom->title
            ];
        }

        return $list;
    }

    public function postAction(Request $request) {
        $allowedProperties = ['title'];

        $atom = new Atom();
        $atom->atomId = Atom::makeUID();
        foreach($allowedProperties as $allowed) {
            if(isset($request->$allowed)) {
                $atom->$allowed = $request->$allowed;
            }
        }
        if(!isset($request->strippedTitle)) {
            $atom->strippedTitle = mb_convert_encoding($request->title, 'ASCII');
        }
        $atom->save();

        return $atom;
    }

    public function getAction($atomId) {
        return Atom::find($atomId);
    }

    public function putAction($atomId) {
        //
    }

    public function deleteAction($atomId) {
        //
    }
}
