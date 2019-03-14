<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use App\Domain;

use App\ApiError;
use App\ApiPayload;
ini_set('memory_limit', '256M');

/**
 * All endpoint methods should return an ApiPayload or Response.
 */
class DomainController extends Controller
{
    /**
     * GET a list of all domains.
     *
     * @api
     *
     * @param integer $productId The current product's id
     *
     * @return ApiPayload|Response
     */
    public function listAction($productId) {
        $domains = Domain::where('product_id', '=', $productId)
                ->orderBy('title', 'ASC') 
                ->get();

        return new ApiPayload($domains);
    }

    /**
     * GET a domain and its atoms.
     *
     * @api
     *
     * @param string $productId The current product's id
     * @param string $code The code of the domain to retrieve
     *
     * @return ApiPayload|Response
     */
    public function getAction($productId, $code) {
        $code = $code == '__none__' ? null : $code;
        $code = str_replace("-", "/", $code); //change Delegating-Prioritizing back to Delegating/Prioritizing

        if($code === null) {
            $domain = ['code' => null];
        }
        else {
            $domain = Domain::where('product_id', '=', $productId)
                    ->where('code', '=', $code)
                    ->first();
        }

        if(!$domain) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested domain could not be found.');
        }

        return new ApiPayload(Domain::addAtoms($domain, $productId));
    }
}
