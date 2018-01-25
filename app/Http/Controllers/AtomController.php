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
    protected $_allowedProperties = ['molecule_code', 'xml', 'status_id', 'domain_code'];
    protected $_allowedMassUpdateProperties = ['molecule_code', 'status_id', 'sort'];

    /**
     * GET a list of all atoms.
     *
     * @api
     *
     * @param integer $productId The current product's id
     *
     * @return ApiPayload|Response
     */
    public function listAction($productId) {
        $list = [];
        $atoms = Atom::allForCurrentProduct()
                ->whereIn('id', Atom::buildLatestIDQuery())
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
     * @param integer $productId The current product's id
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function postAction($productId, Request $request) {
        $input = $request->all();
        $moleculeCode = isset($input['molecule_code']) ? $input['molecule_code'] : null;
        $locked = current(Molecule::locked($moleculeCode, $productId));
        if(isset($moleculeCode) && $locked) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'Chapter "' . $locked->title . '" is locked, and cannot be modified at this time.');
        }

        $atom = new Atom();
        $atom->entity_id = Atom::makeUID();
        $atom->product_id = $productId;
        foreach($this->_allowedProperties as $allowed) {
            if(array_key_exists($allowed, $input)) {
                $atom->$allowed = $input[$allowed];
            }
        }
        $atom->save();
        $atom->is_current = true;

        return new ApiPayload($atom->addAssignments($productId));
    }

    /**
     * GET an atom.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param string $entityId The entityId of the atom to retrieve
     * @param ?string $id (optional) The ID of the specific version to retrieve
     *
     * @return ApiPayload|Response
     */
    public function getAction($productId, $entityId, $id = null) {
        if($id) {
            $atom = Atom::allForCurrentProduct()
                    ->where('id', '=', $id)
                    ->where('entity_id', '=', $entityId)
                    ->get()
                    ->first();

            $currentAtom = Atom::findNewestIfNotDeleted($entityId, $productId);
        }
        else {
            $atom = Atom::findNewestIfNotDeleted($entityId, $productId);
        }

        if(!$atom) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested atom could not be found.');
        }

        $atom->is_current = !$id || $atom->id == $currentAtom->id;
        $atom = $atom->addDomains($productId);
        $atom = $atom->addCommentSuggestions($entityId);
        return new ApiPayload($atom->addAssignments($productId));
    }

    /**
     * Update an atom.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param string $entityId The entityId of the atom to retrieve
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function putAction($productId, $entityId, Request $request) {
        $input = $request->all();

        $locked = current(Molecule::locked($input['molecule_code'], $productId));
        if(isset($input['molecule_code']) && $locked) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'Chapter "' . $locked->title . '" is locked, and cannot be modified at this time.');
        }

        $atom = Atom::findNewest($entityId, $productId);
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

        return new ApiPayload($atom->addAssignments($productId));
    }

    /**
     * Update many atoms.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function massUpdateAction($productId, Request $request) {
        $entityIds = $request->input('entityIds');
        $updates = $request->input('updates');

        $atoms = Atom::findNewest($entityIds, $productId)->get();

        try {
            $moleculeCodes = [];
            foreach($atoms as $atom) {
                $moleculeCodes[] = $atom->molecule_code;
            }

            $locked = current(Molecule::locked($moleculeCodes, $productId));
            if($locked) {
                throw new \Exception('Chapter "' . $locked->title . '" is locked, and cannot be modified at this time.');
            }

            \DB::transaction(function () use ($atoms, $updates, $productId) {
                foreach($atoms as $atomKey => $atom) {
                    $atom = $atom->replicate();
                    foreach($this->_allowedMassUpdateProperties as $allowed) {
                        if(array_key_exists($allowed, $updates)) {
                            $atom->$allowed = $updates[$allowed];
                            if ($allowed == 'status_id'){
                                $atom['massupdate'] = 'massupdate'; //indicating it's from massupdate
                            }
                        }
                    }
                    $atom->save();
                    $atoms[$atomKey] = $atom->addAssignments($productId);
                }
            });
        }
        catch(\Exception $e) {
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
     * @param integer $productId The current product's id
     *
     * @param string $entityId The entityId of the atom to delete
     */
    public function deleteAction($productId, $entityId) {
        //
    }

    /**
     * GET an atom's history.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param string $entityId The entityId of the atom to examine
     *
     * @return ApiPayload|Response
     */
    public function historyAction($productId, $entityId) {
        $versions = Atom::allForCurrentProduct()
                ->where('entity_id', '=', $entityId)
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
     * @param integer $productId The current product's id
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function searchAction($productId, Request $request) {
        $q = strtolower($request->input('q', ''));
        $filters = $request->input('filters', []);
        $limit = max((int)$request->input('limit', 10), 1);
        $page = max((int)$request->input('page', 1), 1);

        $results = $q ? Atom::search($q, $productId, $filters, $limit, $page) : [];
        return new ApiPayload($results);
    }
}
