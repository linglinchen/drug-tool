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
    protected $_allowedProperties = ['xml'];

    public function listAction() {
        $list = [];
        $atoms = Atom::whereIn('id', Atom::latestIDs())
            ->orderBy('alphaTitle', 'asc')
            ->get();
        foreach($atoms as $atom) {
            $firstChar = strtoupper($atom['letter']);
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
        $atom->save();

        return new ApiPayload($atom);
    }

    public function deleteAction($entityId) {
        //
    }

    public function previousAction($entityId) {
        $ids = Atom::latestIDs();
        $currentId = Atom::findNewestIfNotDeleted($entityId)['id'];
        $currentLocation = array_search($currentId, $ids);
        end($ids);
        $targetLocation = $currentLocation ? $currentLocation - 1 : key($ids);
        $atom = Atom::find($ids[$targetLocation]);

        return new ApiPayload([
            'entityId'      => $atom['entityId']
        ]);
    }

    public function nextAction($entityId) {
        $ids = Atom::latestIDs();
        $currentId = Atom::findNewestIfNotDeleted($entityId)['id'];
        $currentLocation = array_search($currentId, $ids);
        reset($ids);
        $targetLocation = $currentLocation < sizeof($ids) - 1 ? $currentLocation + 1 : key($ids);
        $atom = Atom::find($ids[$targetLocation]);

        return new ApiPayload([
            'entityId'    => $atom['entityId']
        ]);
    }

    public function searchAction(Request $request) {
        $q = strtolower($request->input('q', ''));
        $limit = max((int)$request->input('limit', 10), 1);
        $page = max((int)$request->input('page', 1), 1);

        $results = Atom::search($q, $limit, $page);

        return new ApiPayload($results);
    }
}
