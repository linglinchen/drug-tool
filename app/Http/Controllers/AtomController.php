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
    protected $_allowedProperties = ['title'];

    public function listAction() {
        $list = [];
        $atoms = Atom::whereIn('id', Atom::latestIDs())
            ->orderBy('strippedTitle', 'asc')
            ->get();
        foreach($atoms as $atom) {
            $firstChar = strtoupper($atom['strippedTitle'][0]);
            if(!isset($list[$firstChar])) {
                $list[$firstChar] = [];
            }

            $list[$firstChar][] = [
                'id' => $atom->atomId,
                'title' => $atom->title
            ];
        }

        return $list;
    }

    public function postAction(Request $request) {
        $atom = new Atom();
        $atom->atomId = Atom::makeUID();
        foreach($this->_allowedProperties as $allowed) {
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
        return Atom::findNewestIfNotDeleted($atomId);
    }

    public function putAction($atomId, Request $request) {
        $atom = Atom::findNewest($atomId);
        if(!$atom) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        $atom = $atom->replicate();
        foreach($this->_allowedProperties as $allowed) {
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

    public function deleteAction($atomId) {
        //
    }
}
