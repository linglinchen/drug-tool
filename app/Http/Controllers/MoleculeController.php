<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Molecule;

use App\ApiError;
use App\ApiPayload;

/**
 * All endpoint methods should return an ApiPayload or Response.
 */
class MoleculeController extends Controller
{
    /**
     * GET a list of all molecules.
     *
     * @api
     *
     * @param integer $productId The current product's id
     *
     * @return ApiPayload|Response
     */
    public function listAction($productId) {
        $molecules = Molecule::select()->orderBy('sort', 'ASC')->get();

        return new ApiPayload($molecules);
    }

    /**
     * GET a molecule and its atoms.
     *
     * @api
     *
     * @param string $productId The current product's id
     * @param string $code The code of the molecule to retrieve
     *
     * @return ApiPayload|Response
     */
    public function getAction($productId, $code) {
        $code = $code == '__none__' ? null : $code;

        if($code === null) {
            $molecule = ['code' => null];
        }
        else {
            $molecule = Molecule::where('product_id', '=', $productId)
                    ->where('code', '=', $code)
                    ->first();
        }

        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        return new ApiPayload(Molecule::addAtoms($molecule, $productId));
    }
}
