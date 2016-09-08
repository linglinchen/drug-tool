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
 * This controller is responsible for exporting molecules.
 * All endpoint methods should return an ApiPayload or Response.
 */
class MoleculeExportController extends Controller {
    /**
     * Export a molecule.
     *
     * @api
     *
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function getAction($code, Request $request) {
        $statusId = $request->input('statusId');
        $statusId = $statusId === '' ? null : $statusId;

        $molecule = Molecule::where('code', '=', $code)
                ->get()
                ->first();

        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        $zip = new \ZipArchive();
        $filename = $code . '_xml.zip';
        $filepath = tempnam('tmp', $code . '_xml_zip');     //generate the zip in the tmp dir, so it doesn't hang around
        $result = $zip->open($filepath, \ZipArchive::OVERWRITE);

        $xml = $molecule->export($statusId);
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
