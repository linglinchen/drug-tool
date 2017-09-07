<?php

namespace App\Http\Controllers;

use App\Http\Requests\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;

use DB;

use App\Molecule;
use App\Product;
use App\Status;
use App\ApiError;
use App\ApiPayload;

/**
 * This controller is responsible for exporting molecules.
 * All endpoint methods should return an ApiPayload or Response.
 */
class MoleculeExportController extends Controller {
    /**
     * Export a molecule.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function getAction($productId, $code, Request $request) {
//$doctype = (string)Product::find(5)->getDoctype();

        $statusId = $request->input('statusId');
        $statusId = $statusId === '' ? null : $statusId;

//receive statusIds base on product 1, and manipulate to append appropriate leadin for other products
        if ($productId !== 1){
                switch ($statusId) {
                    case 100:
                    $statusId = Status::getDevStatusId($productId);
                        break;
                    case 200:
                    $statusId = Status::getReadyForPublicationStatusId($productId);
                        break;
                    case 300:
                    $statusId = Status::getDeactivatedStatusId($productId);
                        break;

                    default:
                        break;
                }

        }


        // This is a test that shows in network tab of browser what the statusId resolves to sends a Javascript alert to the client

//$message = $doctype;
//echo "<script type='text/javascript'>alert('$message');</script>";

        $molecule = Molecule::allForCurrentProduct()
                ->where('code', '=', $code)
                ->get()
                ->first();

        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        $zip = new \ZipArchive();
        $filename = $code . '_xml.zip';
        $filepath = tempnam('tmp', $code . '_xml_zip');     //generate the zip in the tmp dir, so it doesn't hang around
        $result = $zip->open($filepath, \ZipArchive::OVERWRITE);

        $xml = '<!DOCTYPE drug_guide PUBLIC "-//ES//DTD drug_guide DTD version 3.4//EN//XML" "Y:\WWW1\tools\Drugs\3_4_drug.dtd">' . "\n";
        $xml .= '<drug_guide isbn="9780323448260">' . "\n";     //TODO: make the ISBN dynamic
        $xml .= $molecule->export($statusId);
        $xml .= '</drug_guide>';
        $zip->addFromString($code . '.xml', $xml);

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Access-Control-Expose-Headers: content-type,content-disposition');
        readfile($filepath);
        exit;
    }
}
