<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Atom;
use App\Molecule;

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
    protected $_allowedProperties = ['molecule_code', 'xml', 'status_id'];
    protected $_allowedMassUpdateProperties = ['molecule_code', 'status_id', 'sort'];

    /**
     * GET a list of all atoms.
     *
     * @api
     *
     * @return ApiPayload|Response
     */
    public function listAction() {
        $list = [];
        $atoms = Atom::whereIn('id', Atom::buildLatestIDQuery())
            ->orderBy('alpha_title', 'asc')
            ->get();
        foreach($atoms as $atom) {
            $list[] = [
                'entity_id' => $atom->entityId,
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

        if(isset($input['molecule_code']) && Molecule::isLocked($input['molecule_code'])) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'That chapter is locked, and cannot be modified at this time.');
        }

        $atom = new Atom();
        $atom->entity_id = Atom::makeUID();
        foreach($this->_allowedProperties as $allowed) {
            if(array_key_exists($allowed, $input)) {
                $atom->$allowed = $input[$allowed];
            }
        }
        $atom->save();
        $atom->is_current = true;

        return new ApiPayload($atom->addAssignments());
    }

    /**
     * GET an atom.
     *
     * @api
     *
     * @param string $entityId The entityId of the atom to retrieve
     * @param ?string $id (optional) The ID of the specific version to retrieve
     *
     * @return ApiPayload|Response
     */
    public function getAction($entityId, $id = null) {
        if($id) {
            $atom = Atom::where('id', '=', $id)
                    ->where('entity_id', '=', $entityId)
                    ->get()
                    ->first();

            $currentAtom = Atom::findNewestIfNotDeleted($entityId);
        }
        else {
            $atom = Atom::findNewestIfNotDeleted($entityId);
        }

        if(!$atom) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        $atom->is_current = !$id || $atom->id == $currentAtom->id;

        return new ApiPayload($atom->addAssignments());
    }

    /**
     * Update an atom.
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

        if(isset($input['molecule_code']) && Molecule::isLocked($input['molecule_code'])) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'That chapter is locked, and cannot be modified at this time.');
        }

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
        $atom->is_current = true;

        return new ApiPayload($atom->addAssignments());
    }

    /**
     * Update many atoms.
     *
     * @api
     *
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function massUpdateAction(Request $request) {
        $entityIds = $request->input('entityIds');
        $updates = $request->input('updates');

        $atoms = Atom::findNewest($entityIds)->get();

        try {
            DB::transaction(function () use($atoms, $input) {
                foreach($atoms as $atomKey => $atom) {
                    if(Molecule::isLocked($atom->molecule_code)) {
                        $molecule = Molecule::where('code', '=', $atom->molecule_code)->first();
                        $moleculeTitle = $molecule ? $molecule->title : '';

                        throw new Exception('Chapter "' . $molecule->title . '" is locked, and cannot be modified at this time.');
                    }

                    $atom = $atom->replicate();
                    foreach($this->_allowedMassUpdateProperties as $allowed) {
                        if(array_key_exists($allowed, $updates)) {
                            $atom->$allowed = $updates[$allowed];
                        }
                    }
                    $atom->save();

                    $atoms[$atomKey] = $atom->addAssignments();
                }
            });
        }
        catch(Exception $e) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, $e->getMessage());
        }

        return new ApiPayload($atoms);
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
        $versions = Atom::where('entity_id', '=', $entityId)
                ->orderBy('id', 'ASC')
                ->get();

        if(!$versions) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        return new ApiPayload($versions);
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
