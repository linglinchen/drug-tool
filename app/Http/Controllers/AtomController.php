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
            $list[] = [
                'id' => $atom->id,
                'title' => $atom->title
            ];
        }

        return $list;
    }

    public function postAction() {
        //
    }

    public function getAction($atomId) {
        //
    }

    public function putAction($atomId) {
        //
    }

    public function deleteAction($atomId) {
        //
    }
}
