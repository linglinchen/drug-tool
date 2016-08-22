<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Atom;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller handles atoms.
 * All endpoint methods should return an ApiPayload or Response.
 *
 * @property-read string[] $_allowedProperties The list of writeable properties that are accepted from the client
 */
class AtomController extends Controller
{
    protected $_allowedProperties = ['moleculeCode', 'xml', 'statusId'];

    /**
     * GET a list of all atoms.
     *
     * @api
     *
     * @return ApiPayload|Response
     */
    public function listAction() {
        $list = [];
        $atoms = Atom::whereIn('id', Atom::latestIDs())
            ->orderBy('alphaTitle', 'asc')
            ->get();
        foreach($atoms as $atom) {
            $list[] = [
                'entityId' => $atom->entityId,
                'title' => $atom->title
            ];
        }

        return new ApiPayload($list);
    }

    /**
     * POST a new atom.
     *
     * @api
     *
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function postAction(Request $request) {
        $input = $request->all();

        $atom = new Atom();
        $atom->entityId = Atom::makeUID();
        foreach($this->_allowedProperties as $allowed) {
            if(array_key_exists($allowed, $input)) {
                $atom->$allowed = $input[$allowed];
            }
        }
        $atom->save();

        return new ApiPayload($atom->addAssignments());
    }

    /**
     * GET an atom.
     *
     * @api
     *
     * @param string $entityId The entityId of the atom to retrieve
     *
     * @return ApiPayload|Response
     */
    public function getAction($entityId) {
        $atom = Atom::findNewestIfNotDeleted($entityId);

        if(!$atom) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        return new ApiPayload($atom->addAssignments()->addComments());
    }

    /**
     * GET an atom.
     *
     * @api
     *
     * @param string $entityId The entityId of the atom to retrieve
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function putAction($entityId, Request $request) {
        $input = $request->all();

        $atom = Atom::findNewest($entityId);
        if(!$atom) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        $atom = $atom->replicate();
        foreach($this->_allowedProperties as $allowed) {
            if(array_key_exists($allowed, $input)) {
                $atom->$allowed = $input[$allowed];
            }
        }
        $atom->save();

        return new ApiPayload($atom->addAssignments());
    }

    /**
     * DELETE an atom. This is a soft delete, not a hard one.
     *
     * @todo Implement this.
     * @api
     *
     * @param string $entityId The entityId of the atom to delete
     */
    public function deleteAction($entityId) {
        //
    }

    /**
     * GET an atom's history.
     *
     * @api
     *
     * @param string $entityId The entityId of the atom to examine
     *
     * @return ApiPayload|Response
     */
    public function historyAction($entityId) {
        $versions = Atom::where('entityId', '=', $entityId)->get();

        if(!$versions) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        return new ApiPayload($versions);
    }

    /**
     * GET the ID of the previous atom in alphabetical order.
     *
     * @api
     *
     * @param string $entityId The entityId of the current atom
     *
     * @return ApiPayload|Response
     */
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

    /**
     * GET the ID of the next atom in alphabetical order.
     *
     * @api
     *
     * @param string $entityId The entityId of the current atom
     *
     * @return ApiPayload|Response
     */
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

    /**
     * GET a list of atoms matching the query.
     *
     * @api
     *
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function searchAction(Request $request) {
        $q = strtolower($request->input('q', ''));
        $filters = $request->input('filters', []);
        $limit = max((int)$request->input('limit', 10), 1);
        $page = max((int)$request->input('page', 1), 1);

        $results = $q ? Atom::search($q, $filters, $limit, $page) : [];

        return new ApiPayload($results);
    }
}
