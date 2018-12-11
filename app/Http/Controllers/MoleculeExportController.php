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
		//TODO: build method to fetch product info from db
		$productInfo = [
			'isbn' => '0000000000000',
			'author' => '',
			'title' => '',
			'edition' => 0,
			'cds' => [
				'firstname' => '',
				'lastname' => '',
				'phone' => '1-000-000-0000',
				'email' => '',
			],
		];
		$statusId = $request->input('statusId');
        $statusId = $statusId === '' ? null : (int)$statusId;
        $withFigures = $request->input('withFigures');
        $withFigures = $withFigures === '' ? null : $withFigures;

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

		$moleculeXml = $molecule->export($statusId);

		//If doctype is dictionary, different xml wrapper is written.
		if($doctype === 'dictionary') {
			$xmlDoctype = '<!DOCTYPE dictionary PUBLIC "-//ES//DTD dictionary DTD version 1.0//EN//XML" "https://major-tool-development.s3.amazonaws.com/DTDs/Dictionary_4_5.dtd">';
			$xmlRoot = 'dictionary';

			switch ((int)$productId) { //TODO: make the ISBNs dynamic
				case 3:
					$productInfo = [
						'isbn' => '9780702074639',
						'author' => 'Saunders',
						'title' => 'Veterinary Dictionary',
						'edition' => 5,
						'cds' => [
							'firstname' => 'Sarah',
							'lastname' => 'Vora',
							'phone' => '1 314 447 8326',
							'email' => 'sa.vora@elsevier.com',
						],
					];

					break;
				case 5:
					$productInfo = [
						'isbn' => '9780323546355',
						'author' => 'Mosby',
						'title' => 'Dental Dictionary',
						'edition' => 4,
						'cds' => [
							'firstname' => 'Sarah',
							'lastname' => 'Vora',
							'phone' => '1 314 447 8326',
							'email' => 'sa.vora@elsevier.com',
						],
					];

					break;
				default:
					//nothing special to do here
					break;
			}

			$xml = $xmlDoctype . "\n";
			$xml .= '<' . $xmlRoot . ' isbn="' . $productInfo['isbn'] . '">' . "\n";
			$xml .= '<body>' . "\n";
			$xml .= $moleculeXml;
			$xml .= '</body>' . "\n";
			$xml .= '</'. $xmlRoot . '>';
			$zip->addFromString($code . '.xml', $xml);

			//Top Header material for tab delimited illustration log download. static content added to log.
			$metaheader_default = <<<METAHEADER
Illustration Processing Control Sheet\t\t\t\t\t\t\t\t\t\t\t
Author:\t{$productInfo['author']}\t\t\t\t\t\t\t\t\t\t\tISBN:\t{$productInfo['isbn']}\t\t\t\t
Title:\t{$productInfo['title']}\t\t\t\t\t\t\t\t\t\t\tEdition:\t{$productInfo['edition']}\t\t\t\t
Processor:\t{$productInfo['cds']['firstname']} {$productInfo['cds']['lastname']}\t\t\t\t\t\t\t\t\t\t\tChapter:\t{$code}\t\t\t\t
Phone/Email:\t{$productInfo['cds']['phone']}/{$productInfo['cds']['email']}\t\t\t\t\t\t\t\t\t\t\tDate:\t{$zipDate}\t\t\t\t
Figure Number\tPieces (No.)\tDigital (Y/N)\tTo Come\t Previous edition fig #\t Borrowed from other Elsevier sources (author(s), title, ed, fig #)\tDigital file name (include disc number if multiple discs)\tFINAL FIG FILE NAME\t 1/C HT\t 2/C HT\t 4/C HT\t 1/C LD\t 2/C LD\t 4/C LD\tArt category\tArt point of contact\t Comments\n
METAHEADER;

			$figureLog = $molecule->addFigureLog($moleculeXml, $metaheader_default);
			$zip->addFromString('IllustrationLog_' . $code . '.tsv' ,  $figureLog);
			
			$this->addIllustrations($moleculeXml, $zip, $productInfo);

		//If doctype is questions, different xml wrapper is written.
		} elseif($doctype === 'questions') {
			$xmlDoctype = '<!DOCTYPE questions PUBLIC "-//ES//DTD questions DTD version 1.1//EN//XML" "https://major-tool-development.s3.amazonaws.com/DTDs/questions_1_1.dtd">';
			$xmlRoot = 'questions';
			
			switch ((int)$productId) { //TODO: make the ISBNs dynamic
				case 7:
					$productInfo = [
						'isbn' => '9780702074639',
						'author' => 'Silvestri',
						'title' => 'Saunders 2019-2020 Strategies for Test Success',
						'edition' => 6,
						'cds' => [
							'firstname' => 'Laura',
							'lastname' => 'Goodrich',
							'phone' => '1 314 447 8538',
							'email' => 'l.goodrich@elsevier.com',
						],
					];

					break;
			}

			$xml = $xmlDoctype . "\n";
			$xml .= '<' . $xmlRoot . ' isbn="' . $productInfo['isbn'] . '">' . "\n";
			$xml .= '<chapter id="c' . $code . '" number="' . $code . '">' . "\n";
			$xml .= $moleculeXml;
			$xml .= '</chapter>' . "\n";
			$xml .= '</'. $xmlRoot . '>';
			$zip->addFromString($code . '.xml', $xml);

			//TODO: generate metaheader
			//TODO: get figureLog
			//TODO: consider whether addIllustrationLog should modify zip or not
			//TODO: modify the next few lines
			//TODO: copy this to dictionary doctype above
			$figureLog = $molecule->addFigureLog($moleculeXml, $metaheader_default);
			$zip->addFromString('IllustrationLog_' . $code . '.tsv' ,  $figureLog);
			$this->addIllustrationLog($moleculeXml, $zip, $productInfo);
			
			$this->addIllustrations($moleculeXml, $zip, $productInfo);


		//urecognized and drug doctypes just get orginal code; also skip illustrations for now
		} else {
			$xmlDoctype = '<!DOCTYPE drug_guide PUBLIC "-//ES//DTD drug_guide DTD version 3.4//EN//XML" "https://major-tool-development.s3.amazonaws.com/DTDs/3_8_drug.dtd">';
			$xmlRoot = 'drug_guide';

			$xml = $xmlDoctype . "\n";
			$xml .= '<' . $xmlRoot . ' isbn="' . $productInfo['isbn'] . '">' . "\n";
			$xml .= '<body>' . "\n";
			$xml .= $moleculeXml;
			$xml .= '</body>' . "\n";
			$xml .= '</'. $xmlRoot . '>';
			$zip->addFromString($code . '.xml', $xml);
		}

		$zip->close();

        header('Content-Type: application/zip');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Access-Control-Expose-Headers: content-type,content-disposition');
        readfile($filepath);
        exit;
    }

    /**
     * Count the exportable atoms in a molecule.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param string $code The molecule code
     * @param Request $request The Laravel Request object
     *
     * @return ApiPayload|Response
     */
    public function countAction($productId, $code, Request $request) {
        $statusId = $request->input('statusId');
        $statusId = $statusId === '' ? null : (int)$statusId;

        $count = Molecule::allForCurrentProduct()
                ->where('code', '=', $code)
                ->get()
                ->first()
                ->countExportable($statusId);

        return new ApiPayload($count);
	}
	

	/**
	 * Add illustration files to the export
	 *
	 * @api
	 *
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param object $zip The zip object into which to add the illustrations
	 * @param array $productInfo Information about the current product
	 *
	 * @return boolean Also modifies provided $zip
	 */
	public function addIllustrations($moleculeXml, $zip, $productInfo) {
		//TODO: pick these up from configuration
		$s3UrlDev = 'https://s3.amazonaws.com/metis-imageserver-dev.elseviermultimedia.us';
		$s3UrlProd = 'https://s3.amazonaws.com/metis-imageserver.elseviermultimedia.us';
		//these must be in order of preference
		$imageExtensions = ['eps', 'tif', 'jpg',];

		$imageFiles = $molecule->getImageFileName($moleculeXml);
		foreach($imageFiles as $imageFile) {
			$imageFound = false;
			
			//suggested image
			if(substr($imageFile, 0, 9) == 'suggested') {
				$imageDir = '';

			//legacy image
			} else {
				$imageDir = $productInfo['isbn'] . "/";
			}

			foreach($imageExtensions as $imageExtension) {
				$imagePath = $s3UrlProd . "/" . $imageDir . $imageFile . "." . $imageExtension;
				
				//NOTE: we're ignoring errors because we _expect_ failures and want to quickly skip to next attempt
				if(!$image = @file_get_contents($imagePath . $imageExtension)) {
					if(!$image = @file_get_contents($imagePath . strtoupper($imageExtension))) {
						//not found, check next extension
						continue;
					}
					//found CAPITAL EXTENSION
					$imageFound = true;
					break;
				}
				//found default extension
				$imageFound = true;
				break;

			}

			if($imageFound === true && $image) {
				$zip->addFromString(pathinfo(parse_url($imagePath, PHP_URL_PATH), PATHINFO_BASENAME), $image);

			} else {
				//TODO: log an error message because this was not found
				return false;
			}
		}

		return true;
	}


	/**
	 * Add illustration log to the export
	 *
	 * @api
	 *
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param object $zip The zip object into which to add the illustrations
	 * @param array $productInfo Information about the current product
	 *
	 * @return boolean Also modifies provided $zip
	 */
	public function addIllustrationLog($moleculeXml, $zip, $productInfo) {
		//Top Header material for tab delimited illustration log download. static content added to log.
		$metaheader_default = <<<METAHEADER
Illustration Processing Control Sheet\t\t\t\t\t\t\t\t\t\t\t
Author:\t{$productInfo['author']}\t\t\t\t\t\t\t\t\t\t\tISBN:\t{$productInfo['isbn']}\t\t\t\t
Title:\t{$productInfo['title']}\t\t\t\t\t\t\t\t\t\t\tEdition:\t{$productInfo['edition']}\t\t\t\t
Processor:\t{$productInfo['cds']['firstname']} {$productInfo['cds']['lastname']}\t\t\t\t\t\t\t\t\t\t\tChapter:\t{$code}\t\t\t\t
Phone/Email:\t{$productInfo['cds']['phone']}/{$productInfo['cds']['email']}\t\t\t\t\t\t\t\t\t\t\tDate:\t{$zipDate}\t\t\t\t
Figure Number\tPieces (No.)\tDigital (Y/N)\tTo Come\t Previous edition fig #\t Borrowed from other Elsevier sources (author(s), title, ed, fig #)\tDigital file name (include disc number if multiple discs)\tFINAL FIG FILE NAME\t 1/C HT\t 2/C HT\t 4/C HT\t 1/C LD\t 2/C LD\t 4/C LD\tArt category\tArt point of contact\t Comments\n
METAHEADER;

		$figureLog = $molecule->addFigureLog($moleculeXml, $metaheader_default);
		$zip->addFromString('IllustrationLog_' . $code . '.tsv' ,  $figureLog);

		return true;
	}


}