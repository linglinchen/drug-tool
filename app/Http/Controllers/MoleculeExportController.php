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
        $zip = new ZipArchive();
        $filename = $code . '_xml.zip';
        $filepath = sys_get_temp_dir() . '/' . $filename;
        $zip->open($filepath, ZipArchive::OVERWRITE);

        $xml = Molecule::export($code);
        $zip->addFromString($code . '.xml', $xml);

        $zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename=' . $filename);
        header('Content-Length: ' . filesize($filepath));
        readfile($filepath);
        exit;
    }
}
