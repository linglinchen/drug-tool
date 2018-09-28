<?php

namespace App\Http\Controllers;

use App;

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
        $statusId = $request->input('statusId');
        $statusId = $statusId === '' ? null : (int)$statusId;
        $withFigures = $request->input('withFigures');
        $withFigures = $withFigures === '' ? null : $withFigures;
        $s3UrlDev = 'https://s3.amazonaws.com/metis-imageserver-dev.elseviermultimedia.us';
        $s3UrlProd = 'https://s3.amazonaws.com/metis-imageserver.elseviermultimedia.us';

        //method in Product model to find doctype returns a whole object, so this is extra code to deduce the doctype from the name of the class of the object returned.
        $doctype = 'drug';

        $doctypeObj = Product::find($productId)->getDoctype();
        $doctypeString = get_class($doctypeObj);
        // This is a test that shows in network tab of browser what the statusId resolves to sends a Javascript alert to the client

        if($doctypeString =='App\DictionaryDoctype') {
            $doctype = 'dictionary';
        }

        $molecule = Molecule::allForCurrentProduct()
                ->where('code', '=', $code)
                ->get()
                ->first();

        if(!$molecule) {
            return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }

        $zip = new \ZipArchive();
        $zipDate = date('Y-m-d_H:i:s');
        $filename = $code . '_xml_'. $zipDate .'.zip';
        $filepath = tempnam('tmp', $code . '_xml_zip');     //generate the zip in the tmp dir, so it doesn't hang around
        $result = $zip->open($filepath, \ZipArchive::OVERWRITE);
        //Top Header material for tab delimited illustration log download. static content added to log.
$metaheader_vet = <<<METAHEADER
Illustration Processing Control Sheet\t\t\t\t\t\t\t\t\t\t\t
Author:\tSaunders\t\t\t\t\t\t\t\t\t\t\tISBN:\t9780702074639\t\t\t\t
Title:\tVet Dictionary\t\t\t\t\t\t\t\t\t\t\tEdition:\t5\t\t\t\t
Processor:\tErin Garner\t\t\t\t\t\t\t\t\t\t\tChapter:\t{$code}\t\t\t\t
Phone/Email:\t314 447 8971/e.garner@elsevier.com\t\t\t\t\t\t\t\t\t\t\tDate:\t{$zipDate}\t\t\t\t
Figure Number\tPieces (No.)\tDigital (Y/N)\tTo Come\t Previous edition fig #\t Borrowed from other Elsevier sources (author(s), title, ed, fig #)\tDigital file name (include disc number if multiple discs)\tFINAL FIG FILE NAME\t 1/C HT\t 2/C HT\t 4/C HT\t 1/C LD\t 2/C LD\t 4/C LD\tArt category\tArt point of contact\t Comments\n
METAHEADER;
$metaheader_dental = <<<METAHEADER
Illustration Processing Control Sheet\t\t\t\t\t\t\t\t\t\t\t
Author:\Mosby\t\t\t\t\t\t\t\t\t\t\tISBN:\t9780323546355\t\t\t\t
Title:\tDental Dictionary\t\t\t\t\t\t\t\t\t\t\tEdition:\t4\t\t\t\t
Processor:\tSarah Vora\t\t\t\t\t\t\t\t\t\t\tChapter:\t{$code}\t\t\t\t
Phone/Email:\t314 447 8326/sa.vora@elsevier.com\t\t\t\t\t\t\t\t\t\t\tDate:\t{$zipDate}\t\t\t\t
Figure Number\tPieces (No.)\tDigital (Y/N)\tTo Come\t Previous edition fig #\t Borrowed from other Elsevier sources (author(s), title, ed, fig #)\tDigital file name (include disc number if multiple discs)\tFINAL FIG FILE NAME\t 1/C HT\t 2/C HT\t 4/C HT\t 1/C LD\t 2/C LD\t 4/C LD\tArt category\tArt point of contact\t Comments\n
METAHEADER;

        //If doctype is dictionary, different xml wrapper is written.
        if($doctype === 'dictionary') {
            $moleculeXml = $molecule->export($statusId);
            switch ((int)$productId) { //TODO: make the ISBN dynamic
                case 3:
                    $xml = '<!DOCTYPE dictionary PUBLIC "-//ES//DTD dictionary DTD version 1.0//EN//XML" "Y:\WWW1\METIS\Dictionary_4_3.dtd">' . "\n";
                    $xml .= '<dictionary isbn="9780702074639">' . "\n"; //vet edition 5           vet 4 is 9780702032318
                    $xml .= $moleculeXml;
                    $xml .= '</dictionary>';
                    $figureLog =  $molecule->addFigureLog($moleculeXml, $metaheader_vet);
                    $zip->addFromString($code . '.xml', $xml);
                    $zip->addFromString('IllustrationLog_' . $code . '.tsv' ,  $figureLog);
                    $zip->close();
                    break;
                case 5:
                    $xml = '<!DOCTYPE dictionary PUBLIC "-//ES//DTD dictionary DTD version 1.0//EN//XML" "Y:\WWW1\METIS\Dictionary_4_3.dtd">' . "\n";
                    $xml .= '<dictionary isbn="9780323546355">' . "\n"; //dental edition 4          dental 3 is 9780323100120
                    $xml .= $moleculeXml;
                    $xml .= '</dictionary>';
                    $figureLog = $molecule->addFigureLog($moleculeXml, $metaheader_dental);
                    $zip->addFromString($code . '.xml', $xml);
                    $zip->addFromString('IllustrationLog_' . $code . '.tsv' ,  $figureLog);
                    $imageFiles = $molecule->getImageFileName($moleculeXml);
                    foreach ($imageFiles as $imageFile){
                        if (substr($imageFile, 0, 9) == 'suggested'){ //suggested image
                            $fileName1 = $s3UrlProd."/".$imageFile.".jpg";
                            if (@file_get_contents($fileName1)){
                                $zip->addFromString($imageFile.'.jpg', file_get_contents($fileName1));
                            }
                            $fileName2 = $s3UrlProd."/".$imageFile.".JPG";
                            if (@file_get_contents($fileName2)){
                                $zip->addFromString($imageFile.'.JPG', file_get_contents($fileName2));
                            }
                        }
                        else { //legacy image
                            $zip->addFromString($imageFile.'.jpg', file_get_contents($s3UrlProd."/9780323100120/".$imageFile.".jpg"));
                        }
                    }

                    $zip->close();
                    break;

                default:
                    $xml = '<!DOCTYPE dictionary PUBLIC "-//ES//DTD dictionary DTD version 1.0//EN//XML" "Y:\WWW1\METIS\Dictionary_4_3.dtd">' . "\n";
                    $xml .= '<dictionary isbn="">' . "\n";  //dental edition 4 JUDY used 9780323546355 Mosby nursing drug reference
                    $xml .= $molecule->export($statusId);
                    $xml .= '</dictionary>';
                    $zip->addFromString($code . '.xml', $xml);
                    $zip->close();
                    break;
            }
        }
        else {      //drug doctypes just get orginal code
            $xml = '<!DOCTYPE drug_guide PUBLIC "-//ES//DTD drug_guide DTD version 3.4//EN//XML" "Y:\WWW1\tools\Drugs\3_4_drug.dtd">' . "\n";
            $xml .= '<drug_guide isbn="">' . "\n";     //TODO: make the ISBN dynamic JUDY used 9780323448260
            $xml .= $molecule->export($statusId);
            $xml .= '</drug_guide>';
            $zip->addFromString($code .'_xml_'. date('Y-m-d_H:i:s') .'.xml', $xml);
            $zip->close();
        }

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Access-Control-Expose-Headers: content-type,content-disposition');
        readfile($filepath);
        exit;
    }
}