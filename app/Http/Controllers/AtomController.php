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
            ->orderBy('alphaTitle', 'asc')
            ->get();
        foreach($atoms as $atom) {
            $firstChar = strtoupper($atom['alphaTitle'][0]);
            if(!isset($list[$firstChar])) {
                $list[$firstChar] = [];
            }

            $list[$firstChar][] = [
                'entityId' => $atom->entityId,
                'title' => $atom->title
            ];
        }

        return new ApiPayload($list);
    }

    public function postAction(Request $request) {
        $atom = new Atom();
        $atom->entityId = Atom::makeUID();
        foreach($this->_allowedProperties as $allowed) {
            if($request->input($allowed)) {
                $atom->$allowed = $request->input($allowed);
            }
        }
        $atom->alphaTitle = $request->input('alphaTitle') ?
                $request->input('alphaTitle') :
                mb_convert_encoding($request->input('title'), 'ASCII');     //TODO: do more sanitization here
        $atom->save();

        return new ApiPayload($atom);
    }

    public function getAction($entityId) {
        return new ApiPayload(Atom::findNewestIfNotDeleted($entityId));
    }

    public function putAction($entityId, Request $request) {
        $atom = Atom::findNewest($entityId);
        if(!$atom) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        $atom = $atom->replicate();
        foreach($this->_allowedProperties as $allowed) {
            if($request->input($allowed)) {
                $atom->$allowed = $request->input($allowed);
            }
        }
        $atom->alphaTitle = mb_convert_encoding($request->input('alphaTitle'), 'ASCII');
        $atom->save();

        return new ApiPayload($atom);
    }

    public function deleteAction($entityId) {
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
                'entityId' => $atom->entityId,
                'title' => $atom->title
            ];
        }

        return new ApiPayload($list);
    }
}
