<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use DB;

use App\Molecule;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller is responsible for locking and unlocking molecules.
 * All endpoint methods should return an ApiPayload or Response.
 */
class MoleculeExportController extends Controller {
    /**
     * Lock a molecule.
     *
     * @api
     *
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function lockAction($code, Request $request) {
        $molecule = Molecule::where('code', '=', $code)
                ->get()
                ->first();

        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        $molecule->locked = true;
        $molecule->save();

        new ApiPayload($molecule);
    }
    /**
     * Unlock a molecule.
     *
     * @api
     *
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function unlockAction($code, Request $request) {
        $molecule = Molecule::where('code', '=', $code)
                ->get()
                ->first();

        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        $molecule->locked = false;
        $molecule->save();

        new ApiPayload($molecule);
    }
}
