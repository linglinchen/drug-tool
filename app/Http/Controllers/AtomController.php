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

        return new ApiPayload($list);
    }

    public function postAction(Request $request) {
        $atom = new Atom();
        $atom->atomId = Atom::makeUID();
        foreach($this->_allowedProperties as $allowed) {
            if($request->input($allowed)) {
                $atom->$allowed = $request->input($allowed);
            }
        }
        if(!$request->input('strippedTitle')) {
            $atom->strippedTitle = mb_convert_encoding($request->input('title'), 'ASCII');
        }
        $atom->save();

        return new ApiPayload($atom);
    }

    public function getAction($atomId) {
        return new ApiPayload(Atom::findNewestIfNotDeleted($atomId));
    }

    public function putAction($atomId, Request $request) {
        $atom = Atom::findNewest($atomId);
        if(!$atom) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        $atom = $atom->replicate();
        foreach($this->_allowedProperties as $allowed) {
            if($request->input($allowed)) {
                $atom->$allowed = $request->input($allowed);
            }
        }
        if(!$request->input('strippedTitle')) {
            $atom->strippedTitle = mb_convert_encoding($request->input('title'), 'ASCII');
        }
        $atom->save();

        return new ApiPayload($atom);
    }

    public function deleteAction($atomId) {
        //
    }

    public function searchAction(Request $request) {
        $q = strtolower($request->input('q', ''));
        $page = max((int)$request->input('page', 1), 1);
        $pageSize = max((int)$request->input('pageSize', 10), 1);

        if(strlen($q) > 2) {
            $atoms = Atom::search($q)->get();
        }
        else {
            $atoms = [];
        }

        $list = [];
        foreach($atoms as $atom) {
            $list[] = [
                'id' => $atom->atomId,
                'title' => $atom->title
            ];
        }

        return new ApiPayload($list);
    }
}
