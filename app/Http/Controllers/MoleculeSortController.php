<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use DB;

use App\Atom;
use App\Molecule;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller is responsible for saving the sort order of atoms within a molecule.
 * All endpoint methods should return an ApiPayload or Response.
 */
class MoleculeSortController extends Controller {
    /**
     * Save the atoms' new sort order.
     *
     * @api
     *
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function putAction($code, Request $request) {
        $molecule = Molecule::where('code', '=', $code)
                ->get()
                ->first();
        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        $atomEntityIds = $request->input('atomEntityIds');
        if(!$atomEntityIds || !is_array($atomEntityIds)) {
            return ApiError::buildResponse(Response::HTTP_BAD_REQUEST, 'Missing atomEntityIds.');
        }

        $atoms = Atom::where('molecule_code', '=', $code)
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->whereIn('entity_id', $request->input('atomEntityIds'))
                ->get();

        DB::transaction(function () use($atoms, $atomEntityIds) {
            foreach($atoms as $atom) {
                $newSort = array_search($atom->entity_id, $atomEntityIds) + 1;
                if($atom->sort == $newSort) {
                    continue;       //skip if unchanged
                }

                $atom = $atom->replicate();
                $atom->sort = $newSort;
                $atom->save();
            }
        });

        return new ApiPayload(Molecule::addAtoms($molecule));
    }
}
