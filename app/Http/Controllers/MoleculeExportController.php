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

		//getDoctype() method in Product model returns a whole object containing className, but this is simpler to get directly from model
		$doctype = Product::find($productId)->doctype;

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

		$moleculeXml = $molecule->export($statusId, $doctype);

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

			$molecule->getIllustrationLog($moleculeXml, $zip, $productInfo, $code, $zipDate);
			
			$molecule->getIllustrations($moleculeXml, $zip, $productInfo, $code);

		//If doctype is questions, different xml wrapper is written.
		} elseif($doctype === 'question') {
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
			$xml .= $moleculeXml;
			$xml .= '</'. $xmlRoot . '>';
			$zip->addFromString($code . '.xml', $xml);

			$molecule->getIllustrationLog($moleculeXml, $zip, $productInfo, $code, $zipDate);
			
			$molecule->getIllustrations($moleculeXml, $zip, $productInfo, $code);


		//both urecognized and drug doctypes get original code; also skip illustrations for now
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

}