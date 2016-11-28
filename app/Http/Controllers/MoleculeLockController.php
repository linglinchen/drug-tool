<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use DB;

use App\Molecule;
use App\AccessControl;

use App\ApiError;
use App\ApiPayload;

/**
 * This controller is responsible for locking and unlocking molecules.
 * All endpoint methods should return an ApiPayload or Response.
 */
class MoleculeLockController extends Controller {
    /**
     * Lock a molecule.
     *
     * @api
     *
     * @param string $productId The current product's id
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function lockAction($productId, $code, Request $request) {
        return $this->_changeLockState($productId, $code, $request, true);
    }

    /**
     * Unlock a molecule.
     *
     * @api
     *
     * @param string $productId The current product's id
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function unlockAction($productId, $code, Request $request) {
        return $this->_changeLockState($productId, $code, $request, false);
    }

    /**
     * Lock or unlock a molecule.
     *
     * @param string $productId The current product's id
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     * @param boolean $lock Lock or unlock the molecule
     *
     * @return ApiPayload|Response
     */
    protected function _changeLockState($productId, $code, $request, $lock) {
        $accessControl = new AccessControl();
        if(!\Auth::user()->ACL->can('lock_molecules')) {
            return ApiError::buildResponse(Response::HTTP_FORBIDDEN, 'You do not have access to this resource.');
        }

        $molecule = Molecule::allForCurrentProduct()
                ->where('code', '=', $code)
                ->get()
                ->first();

        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        $molecule->locked = $lock;
        $molecule->save();

        return new ApiPayload($molecule);
    }
}
