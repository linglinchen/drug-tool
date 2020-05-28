<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\AppModel;
use App\Atom;
use App\Assignment;
use App\Comment;
use App\Status;
use App\BookDoctype;

class Molecule extends AppModel {
    use SoftDeletes;

    protected $table = 'molecules';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];

    /*
     * Returns all molecule titles as an associative array.
     * code => title
     *
     * @param integer $productId Limit to this product
     *
     * @return string[]
     */
    public static function getLookups($productId) {
        $output = [];
        $molecules = self::allForProduct($productId);
        foreach($molecules as $molecule) {
            $output[$molecule['code']] = $molecule['title'];
        }

        return $output;
    }

    /**
     * Add atoms to the molecule.
     *
     * @param integer $productId Limit to this product
     *
     * @param mixed[] $molecule The molecule
     */
    public static function addAtoms($molecule, $productId) {
        $productId = (int)$productId;       //protect against sql injection attacks
        $atoms = Atom::allForProduct($productId)
        ->select('*', DB::raw('coalesce(sort, 0)'))
                ->where('molecule_code', '=', $molecule['code'])
                ->whereIn('id', function ($q) {
                    Atom::BuildLatestIDQuery(null, $q);
                })
                ->orderBy('coalesce', 'ASC')
                ->orderBy('created_at', 'DESC')
                ->get();

        //get assignments for each atom
        //join atoms and assignments tables, atom will be the lastest version, not deleted
        $sql_assignment =
            "SELECT ass.*
            FROM atoms a
            JOIN assignments ass ON a.entity_id = ass.atom_entity_id
            WHERE product_id = " . $productId . "
                and molecule_code = '" . $molecule['code'] . "'
                and a.id in
                    (SELECT id FROM atoms WHERE id in
                        (SELECT MAX(id) FROM atoms where product_id=" . $productId . " GROUP BY entity_id)
                    and deleted_at IS NULL)
            ORDER BY sort ASC, ass.id ASC";

        $assignmentsByAtom = [];
        $assignments = DB::select($sql_assignment);
        $assignmentsArray = json_decode(json_encode($assignments), true);

        foreach($assignmentsArray as $assignment) {
            $assignmentsByAtom[$assignment['atom_entity_id']][] = $assignment;
        }

        //get comments for each atom
        $sql_comment =
            "SELECT c.*
            FROM atoms a
            JOIN comments c ON a.entity_id = c.atom_entity_id
            WHERE product_id = " . $productId . "
                and molecule_code = '" . $molecule['code'] . "'
                and a.id in
                    (SELECT id FROM atoms WHERE id in
                        (SELECT MAX(id) FROM atoms where product_id=" . $productId . " GROUP BY entity_id)
                    and deleted_at IS NULL )
            ORDER BY c.id DESC"; //join atoms and comments tables, atom will be the lastest version, not deleted

        $commentsFigure = [];
        $commentsByAtom = [];
        $commentSummaries = [];
        $comments = DB::select($sql_comment);
        $commentsArray = json_decode(json_encode($comments), true);

        foreach($commentsArray as $comment) {
            if(strpos($comment['text'], 'type="figure"') !== false) {
                $commentsInfo = [];
                $commentXml = '<?xml version="1.0" encoding="UTF-8"?><documents>'.$comment['text'].'</documents>';
                $xmlObject = simplexml_load_string($commentXml, 'SimpleXMLElement', LIBXML_NOERROR);
                $reviewPart = $xmlObject->xpath('//query[@type="figure"]/suggestion/text()');
                $reviewStatusObj = $reviewPart ? $reviewPart[0] : '';
                $reviewStatus = $reviewStatusObj ? json_decode(json_encode($reviewStatusObj), true)[0] : '';

                $captionPart = $xmlObject->xpath('//query[@type="figure"]/component[@type="figure"]/ce_caption/text()');
                $captionObj = $captionPart ? $captionPart[0] : '';
                $caption = $captionObj ? json_decode(json_encode($captionObj), true)[0] : '';

                $creditPart = $xmlObject->xpath('//query[@type="figure"]/component[@type="figure"]/credit/text()');
                $creditObj = $creditPart ? $creditPart[0] : '';
                $credit = $creditObj ? json_decode(json_encode($creditObj), true)[0] : '';

                $availabilityPart = $xmlObject->xpath('//query[@type="figure"]/availability/text()');
                $availabilityObj = $availabilityPart ? $availabilityPart[0] : '';
                $availability = $availabilityObj ? json_decode(json_encode($availabilityObj), true)[0] : '';

                $figureFilePart = $xmlObject->xpath('//query[@type="figure"]/component[@type="figure"]/file/@src');
                $figureFileObj = $figureFilePart ? $figureFilePart[0] : '';
                $figureFile = $figureFileObj ?
                        json_decode(json_encode($figureFileObj), true)['@attributes']['src'] :
                        '';

                $commentsInfo['reviewstatus'] = $reviewStatus;
                $commentsInfo['availability'] = $availability;
                $commentsInfo['caption'] = $caption;
                $commentsInfo['credit'] = $credit;
                $commentsInfo['figurefile'] = $figureFile;
                $commentsInfo['text'] = $comment['text'];
                $commentsInfo['id'] = $comment['id'];
                $commentsFigure[$comment['atom_entity_id']][] = $commentsInfo;
            }

            $commentsByAtom[$comment['atom_entity_id']][] = $comment;
        }

        foreach($commentsByAtom as $entityId => $group) {
            $commentSummaries[$entityId] = [
                'count' => sizeof($group),
                'last_comment' => [
                    'date' => sizeof($group) ? $group[0]['created_at'] : null,
                    'user_id' => sizeof($group) ? $group[0]['user_id'] : null
                ]
            ];
        }

        foreach($atoms as $key => $atom) {
            $atom->addDomains($productId);
            $atom['xmlFigures'] = strpos($atom->xml, 'type="figure"') !== false;
            $atom = $atom->toArray();
            $atom['assignments'] = array_key_exists($atom['entity_id'], $assignmentsByAtom) ?
                    $assignmentsByAtom[$atom['entity_id']] :
                    [];
            $atom['suggestedFigures'] = array_key_exists($atom['entity_id'], $commentsFigure) ?
                    $commentsFigure[$atom['entity_id']] :
                    [];
            $atom['commentSummary'] = array_key_exists($atom['entity_id'], $commentSummaries) ?
                    $commentSummaries[$atom['entity_id']] :
                    null;
            unset($atom['xml']);
            $atoms[$key] = $atom;
        }

        $molecule['atoms'] = $atoms;
        return $molecule;
    }

	
	/**
	 * Export the molecule to XML. Takes the LATEST "Ready to Publish" VERSION of each atom that matches the statusId
	 * (if passed).
	 *
	 * @param integer $statusId The atom (?!) statuses to include
	 * @param string $doctype The molecule's doctype
	 * @param integer $withFigures Whether to include figures or not (unused)
	 *
	 * @return string/array
	 */
	public function export($statusId = null, $doctype='drug', $withFigures=0) {
		//Below diverts to the separate 'getExportSortOrder' so that only Ready to publish atoms are in sort. Plain
		//'getSortOrder' always chooses current atoms, so this separate sort order is needed for the export.
        ini_set('memory_limit', '2560M');
        ini_set('max_execution_time', 300);
        $orderedIds = $this->_getExportSortOrder($statusId);

		$orderedAtoms = $this->_getMysortedPublishedAtoms($orderedIds);

        $atoms = $orderedAtoms;
		
		//the root element and @attribute(s) of a single molecule
		$xmlMolecule = [
			'dictionary' => [
				'root' => 'alpha',
				'attributes' => [
					'letter' => $this->code,
				],
                'xmlDeclaration' => false,
			],
			'drug' => [
				'root' => 'alpha',
				'attributes' => [
					'letter' => $this->code,
				],
                'xmlDeclaration' => false,
			],
			'question' => [
				'root' => 'chapter',
				'attributes' => [
					'id' => 'c' . $this->code,
					'number' => $this->code,
				],
                'xmlDeclaration' => false,
			],
            'book' => [
				'root' => 'chapter',
				'attributes' => [
					'id' => 'c' . $this->code,
					'code' => $this->code,
				],
                'xmlDeclaration' => false,
			],
			'xhtml' => [
				'root' => false,
				'attributes' => [
					'id' => 'html.' . $this->code,
                ],
                'xmlDeclaration' => true,
			],

		];

        //each atom is packaged separately and not into a molecule
        if($xmlMolecule[$doctype]['root'] === false) {
            $xml = array();
            foreach($atoms as $atom) {
                $atomXml = $atom->export();
                $atomXml = "\t\t" . str_replace("\n", "\n\t\t", $atomXml);      //indent the atom

                if($xmlMolecule[$doctype]['xmlDeclaration']) {
                    $atomXml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n" . $atomXml;
                }

                foreach($xmlMolecule[$doctype]['attributes'] as $attName => $attValue) {
                    //$atomXml .= ' ' . $attName . '="' . $attValue .'"';
                }

                $xml[] = $atomXml;
            }

        
        //combine into molecule
        } elseif($xmlMolecule[$doctype]['root'] !== false) {
            $xml = '';
            if($xmlMolecule[$doctype]['xmlDeclaration']) {
                $xml .= '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
            }

            $xml .= "\t" . '<' . $xmlMolecule[$doctype]['root'];
            foreach($xmlMolecule[$doctype]['attributes'] as $attName => $attValue) {
                $xml .= ' ' . $attName . '="' . $attValue .'"';
            }
            $xml .=  '>' . "\n";

            foreach($atoms as $atom) {
                $atomXml = $atom->export();
                $atomXml = "\t\t" . str_replace("\n", "\n\t\t", $atomXml);      //indent the atom
                $xml .= $atomXml . "\n";
            }
            
            $xml .= "\t" . '</' . $xmlMolecule[$doctype]['root'] . '>' . "\n";
        }

		return $xml;
	}

    /**
     * Take the xml from above and reduce it to a log of figures.
     *
     * @return string A TSV string
     */
    public function addFigureLog($moleculeXml, $metaheader) {
        $ob = simplexml_load_string($moleculeXml, 'SimpleXMLElement', LIBXML_NOERROR);
        $figureLogRows = $metaheader;
        $figureNodes = $ob->$moleculeXml->xpath('//component[@type="figure"]');

        if($figureNodes) {
            $figureRows =" \t";

            foreach($figureNodes as $figureNode) {

                //get parent headword/s that this figure is under
                $temp = $figureNode->xpath('.//ancestor::entry/headw');

                if(isset($temp[1])){
                    $Dom = dom_import_simplexml($temp[1]);
                    $closestEntryNode1 = $Dom->textContent;
                }
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $closestEntryNode0 = $Dom->textContent;
                }
                if(isset($closestEntryNode0) && isset($closestEntryNode1)){
                    $term = $closestEntryNode0 . '/' . $closestEntryNode1;
                    unset($closestEntryNode1);
                }else{
                    if (isset($closestEntryNode0)){
                        $term = $closestEntryNode0;
                    }
                    else{
                        $term = '';
                    }
                }
                unset($temp);

                $temp = $figureNode->xpath('.//credit');
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $credit = $Dom->textContent;
                }else{
                    $credit = "";
                }

                $temp = $figureNode->xpath('.//fullcredit');
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $creditFull = $Dom->textContent;
                }else{
                    $creditFull = "";
                }

                $temp = $figureNode->xpath('.//caption');
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $caption = $Dom->textContent;
                }else{
                    $caption = "";
                }

                //Normalize/remove tab/LF/CR from data
                $term = preg_replace('/[\r\n\t]+/', '', $term);
                $credit = preg_replace('/[\r\n\t]+/', '', $credit);
                $creditFull = preg_replace('/[\r\n\t]+/', '', $creditFull);
                $caption = preg_replace('/[\r\n\t]+/', '', $caption);

                $figureNode = json_encode($figureNode);
                $figureNode = json_decode($figureNode, true);

                $availability = isset($figureNode['@attributes']['availability']) ? $figureNode['@attributes']['availability'] : '';
                if($availability == 'electronic') {
                    $availability = 'online only';
                }
                else if($availability == 'print') {
                    $availability = 'print only';
                }
                else if($availability == 'both') {
                    $availability = 'print and online';
                }
                else {
                    $availability = '';
                }

                //Format data elements into tsv format
                if(isset($figureNode['@attributes']) && isset($figureNode['@attributes']['id'])) {
                    if(isset($figureNode['file'])) {
                        if(count($figureNode['file']) > 1) {
                            foreach($figureNode['file'] as $file) {
                                if(isset($file['@attributes']) && isset($file['@attributes']['src'])) {
                                    $figureRows .= "\n" . $term . "\t\tYes\t\t" . $figureNode['@attributes']['id'] .
                                            "\t" . $caption ."\t". $credit . "\t" . $creditFull. "\t". $file['@attributes']['src'] .
                                            "\t\t\t\t\t\t\t\t\t". "Comp\t".$availability.' ';
                                }
                                else if(isset($file['src'])) {  //for situation when abdomen: [0]=>
                                    $figureRows .= "\n" . $term."\t\tYes\t\t" . $figureNode['@attributes']['id'] .
                                            "\t" . $caption ."\t". $credit."\t" . $creditFull. "\t". $file['src'] .
                                        "\t\t\t\t\t\t\t\t\t" . "Comp\t" . $availability.' ';
                                }

                            }
                        }
                        else if(isset($figureNode['file']['@attributes']) && isset($figureNode['file']['@attributes']['src'])) {
                            $figureRows .= "\n" . $term . "\t\tYes\t\t" . $figureNode['@attributes']['id'] . "\t" . $caption ."\t".
                                    $credit . "\t" . $creditFull. "\t". $figureNode['file']['@attributes']['src'] .
                                    "\t\t\t\t\t\t\t\t\t" . "Comp\t" . $availability . ' ';
                        }
                    }
                    else if(isset($figureNode['p']) && isset($figureNode['p']['@attributes']) && isset($figureNode['p']['@attributes']['src_stub'])) {
                        //img situation: [p]->[src_stub] is equal to [file][src]  ?
                        $figureRows .= "\n" . $term . "\t\tYes\t\t" . $figureNode['@attributes']['id'] . "\t" . $caption ."\t".
                                $credit . "\t" . $creditFull. "\t". $figureNode['p']['@attributes']['src_stub'] .
                                "\t\t\t\t\t\t\t\t\t" . "Comp\t" . $availability . ' ';
                    }
                }
            }

            $figureLogRows .= $figureRows;

        }
        else {
            $figureLogRows .= '';
        }

        //start table info
        $tableNodes = $ob->$moleculeXml->xpath("//*[name()='ce:table']");

        if($tableNodes) {
            $tableRows =" \t";
            $tableLogRows = '';
            foreach($tableNodes as $tableNode) {
                $term = "";
                $productId = (int)self::getCurrentProductId();
                $doctype = Product::find($productId)->getDoctype();
                $term = $doctype->detectTitle($moleculeXml);

                $temp = $tableNode->xpath(".//*[name()='ce:source']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $creditFull = $Dom->textContent;
                }else{
                    $creditFull = "";
                }

                $temp = $tableNode->xpath(".//*[name()='ce:caption']//*[name()='ce:simple-para']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $caption = $Dom->textContent;
                }else{
                    $caption = "";
                }

               $temp = $tableNode->xpath(".//*[name()='ce:legend']//*[name()='ce:simple-para']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $legend = $Dom->textContent;
                }else{
                    $legend = "";
                }

                $temp = $tableNode->xpath(".//*[name()='ce:label']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $label = $Dom->textContent;
                }else{
                    $label = "";
                }
                unset($temp);

                //Normalize/remove tab/LF/CR from data
                $term = preg_replace('/[\r\n\t]+/', '', $term);

                $creditFull = preg_replace('/[\r\n\t]+/', '', $creditFull);
                $caption = preg_replace('/[\r\n\t]+/', '', $caption);
                $legend = preg_replace('/[\r\n\t]+/', '', $legend);

                $tableNode = json_encode($tableNode);
                $tableNode = json_decode($tableNode, true);

                $id = '';
                if (isset($tableNode['@attributes']) && isset($tableNode['@attributes']['id'])){
                    $id = $tableNode['@attributes']['id'];
                }
                //Format data elements into tsv format
                if (strlen($creditFull) > 0){
                    $tableRows .= "\n" . $label . "\t\t". "Yes". "\t\t". $id . "\t" . $legend ."\t".
                                    $creditFull. "\t\t\t" .
                                    "\t\t\t\t\t\t\t\tComp" . "\tprint and online" . ' ';
                }
            }
            $figureLogRows .= $tableRows;
        }
        else {
            $figureLogRows .= '';
        }

        //start ce:figure info
        $ceFigureNodes = $ob->$moleculeXml->xpath("//*[name()='ce:figure']");

        if($ceFigureNodes) {
            $ceFigureRows =" \t";
            $ceFigureLogRows = '';

            foreach($ceFigureNodes as $ceFigureNode) {
                $term = "";
                $productId = (int)self::getCurrentProductId();
                $doctype = Product::find($productId)->getDoctype();
                $term = $doctype->detectTitle($moleculeXml);

                $temp = $ceFigureNode->xpath(".//*[name()='ce:source']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $creditFull = $Dom->textContent;
                }else{
                    $creditFull = "";
                }

                $temp = $ceFigureNode->xpath(".//*[name()='ce:caption']//*[name()='ce:simple-para']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $caption = $Dom->textContent;
                }else{
                    $caption = "";
                }

               $temp = $ceFigureNode->xpath(".//*[name()='ce:legend']//*[name()='ce:simple-para']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $legend = $Dom->textContent;
                }else{
                    $legend = "";
                }

                $temp = $ceFigureNode->xpath(".//*[name()='ce:link']");
                if(isset($temp[0]) && isset($temp[0]['locator']) && isset($temp[0]['locator'][0])){
                    $link = $temp[0]['locator'][0];
                }else{
                    $link = "";
                }

                $temp = $ceFigureNode->xpath(".//*[name()='ce:label']");
                if(isset($temp[0])){
                    $Dom = dom_import_simplexml($temp[0]);
                    $label = $Dom->textContent;
                }
                else{
                    $label = '';
                }

                unset($temp);

                //Normalize/remove tab/LF/CR from data
                $term = preg_replace('/[\r\n\t]+/', '', $term);

                $creditFull = preg_replace('/[\r\n\t]+/', '', $creditFull);
                $caption = preg_replace('/[\r\n\t]+/', '', $caption);
                $legend = preg_replace('/[\r\n\t]+/', '', $legend);

                $ceFigureNode = json_encode($ceFigureNode);
                $ceFigureNode = json_decode($ceFigureNode, true);

                $id = '';
                if (isset($ceFigureNode['@attributes']) && isset($ceFigureNode['@attributes']['id'])){
                    $id = $ceFigureNode['@attributes']['id'];
                }

                //Format data elements into tsv format
                $ceFigureRows .= "\n" . $label . "\t\t". "Yes". "\t\t". $id . "\t" . $caption ."\t".
                                $creditFull. "\t\t". $link . "\t" .
                                "\t\t\t\t\t\t\t\tComp" . "\tprint and online" . ' ';
            }
            $figureLogRows .= $ceFigureRows;
        }
        else {
            $figureLogRows .= '';
        }

        return $figureLogRows;
    }

    /**
     * Take the xml from above and return an array of image file names
     * 
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param array $productInfo Information about the current product
	 * @param array $basepath (optional) Array of components used for a repeated URL path
     *
     * @return array
     */
    public function getImageFileName($moleculeXml, $productInfo, $basepath=false) {
        $ob = simplexml_load_string($moleculeXml, 'SimpleXMLElement', LIBXML_NOERROR);
        $imageFiles = [];
        if ($productInfo['doctype'] == 'book') {
            $figureNodes = $ob->$moleculeXml->xpath('//*[name()="ce:figure"]//*[name()="ce:link"]');
            if($figureNodes) {
                $figureNodes = json_encode($figureNodes);
                $figureNodes = (array)json_decode($figureNodes, true);

                foreach($figureNodes as $figureNode) {
                    if(isset($figureNode['@attributes']) && isset($figureNode['@attributes']['locator'])) {
                        $imageFiles[] = $figureNode['@attributes']['locator'];
                    }
                }
            }

        } elseif($productInfo['doctype'] == 'xhtml') {
            $doc = new \DOMDocument();
            $xsl = new \XSLTProcessor();

            $doc->loadXML($moleculeXml);

            $xsldoc = new \DOMDocument();

            $xsldoc->load('../app/Http/Controllers/doctype/' . $productInfo['doctype'] .'/export_multimedia.xslt');

            $xsl->importStyleSheet($xsldoc);

            $parameters = array(
                'output_format' => 'fullpath',
            );

            $parameters = array_merge($parameters, $basepath);

            $xsl->setParameter('', $parameters);

            $imageFiles = array_filter(explode("\n", $xsl->transformToXML($doc)));

        } else {
            $figureNodes = $ob->$moleculeXml->xpath('//component[@type="figure"]');
            if($figureNodes) {
                $figureNodes = json_encode($figureNodes);
                $figureNodes = (array)json_decode($figureNodes, true);

                foreach($figureNodes as $figureNode) {
                    if(isset($figureNode['@attributes']) && isset($figureNode['@attributes']['id'])) {
                        if(isset($figureNode['file'])) {
                            if(count($figureNode['file']) > 1) {
                                foreach($figureNode['file'] as $file) {
                                    if(isset($file['@attributes']) && isset($file['@attributes']['src'])) {
                                        $imageFiles[] = $file['@attributes']['src'];
                                    }
                                    else if(isset($file['src'])) {  //for situation when abdomen: [0]=>
                                        $imageFiles[] = $file['src'];
                                    }
                                }
                            }
                            else if(
                                isset($figureNode['file']['@attributes']) &&
                                isset($figureNode['file']['@attributes']['src'])
                            ) {
                                $imageFiles[] = $figureNode['file']['@attributes']['src'];
                            }
                        }
                        else if(
                            isset($figureNode['p']) && isset($figureNode['p']['@attributes']) &&
                            isset($figureNode['p']['@attributes']['src_stub'])
                        ) {
                            //img situation: [p]->[src_stub] is equal to [file][src]
                            $imageFiles[] = $figureNode['p']['@attributes']['src_stub'];
                        }
                    }
                }
            }
        }

        return $imageFiles;
    }


	/**
	 * Get illustration files and add to the export zip
	 *
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param object $zip The zip object into which to add the illustrations
	 * @param array $productInfo Information about the current product
	 * @param array $code The molecule code being written
     * @param string $zipDir (optional) A location in zip to store images if not root
	 *
	 * @return boolean Also modifies provided $zip
	 */
	public function getIllustrations($moleculeXml, $zip, $productInfo, $code, $zipDir='') {
   
        //TODO: pick these up from configuration
        //NOTE: ** Disable Zscaler to get this working on Local **
		$s3UrlDev = 'https://s3.amazonaws.com/metis-imageserver-dev.elseviermultimedia.us';
        $s3UrlProd = 'https://s3.amazonaws.com/metis-imageserver.elseviermultimedia.us';
        
        //array of URL constructors
        $basepath = array(
            'imageserver' => $s3UrlProd . "/",
            'legacy' => ($productInfo['doctype'] == 'book' ? $productInfo['isbn'] . "/" : $productInfo['isbn_legacy'] . "/"),
            'suggested' => '',
        );

		//these must be in order of preference
		$imageExtensions = ['eps', 'tif', 'jpg',];

		$molecule = Molecule::allForCurrentProduct()
				->where('code', '=', $code)
				->get()
				->first();

		if(!$molecule) {
			return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
		}

        $imageFiles = $this->getImageFileName($moleculeXml, $productInfo, $basepath);

		foreach($imageFiles as $imageFile) {
			$imageFound = false;
			
			//suggested image
			if(substr($imageFile, 0, 9) == 'suggested') {
				$imageDir = $basepath['suggested'];

			//legacy image
			} else {
				$imageDir = $basepath['legacy'];
			}

            $imagePath = strpos($imageFile, 'https://') !== false ? $imageFile : $basepath['imageserver'] . $imageDir . $imageFile;

			foreach($imageExtensions as $imageExtension) {
                error_log('imageExt:' . $imagePath . '{.' . $imageExtension . "}\n", 3, "/var/www/logs/drug-tool.log");
                //not a filestub, no need to look by extension
                if($imagePath == $imageFile
                    && $image = @file_get_contents($imagePath)) {
                    //set false so it is not appended to file placed in zip
                    $imageExtension = false;

                //NOTE: we're ignoring what should be 404 errors because we _expect_ failures and want to quickly skip to next attempt
                } elseif(!$image = @file_get_contents($imagePath . "." . $imageExtension)) {
                    if(!$image = @file_get_contents($imagePath . "." . strtoupper($imageExtension))) {
                        //not found, check next extension
                        continue;
                    }
                    //found CAPITAL EXTENSION (ick)
                    $imageFound = true;
                    break;
                }
                //found default or forced extension
                $imageFound = true;
                break;

			}

			//incidentally this will rename everything to use a lowercase extension; adds extension to filename if needed
			if($imageFound === true && $image) {
                $imageZipFilename = pathinfo($imageFile, PATHINFO_FILENAME);
                if($imageExtension) {
                    $imageZipExtension = '.' . $imageExtension;
                } else {
                    $imageZipExtension = '.' . pathinfo($imageFile, PATHINFO_EXTENSION);
                }

				$zip->addFromString($zipDir . $imageZipFilename . $imageZipExtension, $image);

			} else {
				//TODO: log an error message because this was not found
				//$zip->addFromString('FAILED_'.pathinfo(parse_url($imagePath . "." . $imageExtension, PHP_URL_PATH) . '.txt', PATHINFO_BASENAME), $imagePath . "." . $imageExtension);
			}
		}

		return true;
	}


	/**
	 * Get illustration log and add to the export zip
	 *
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param object $zip The zip object into which to add the illustrations
	 * @param array $productInfo Information about the current product
	 * @param array $code The molecule code being written
	 * @param array $zipDate The date on which this zip is being created and at which time the illustration log was generated
     * @param string $zipDir (optional) A location in zip to store images if not root
     * @param string $moleculeIndex (optional) A counter iterating the molecule XML files
	 *
	 * @return boolean Also modifies provided $zip
	 */
	public function getIllustrationLog($moleculeXml, $zip, $productInfo, $code, $zipDate, $zipDir='', $moleculeIndex=0) {
		$figureLog = $this->createIllustrationLog($moleculeXml, $productInfo, $code, $zipDate, $moleculeIndex);

        if($productInfo['doctype'] == 'xhtml') {
            $zip->addFromString($zipDir . 'dataset.xml' ,  $figureLog);
        } else {
            $zip->addFromString($zipDir . 'IllustrationLog_' . $code . '.tsv' ,  $figureLog);
        }

		return true;
	}


	/**
	 * Create header and fetch content for illustration log
	 *
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param array $productInfo Information about the current product
	 * @param array $code The molecule code being written
	 * @param array $zipDate The date on which this zip is being created and at which time the illustration log was generated
     * @param string $moleculeIndex (optional) A counter iterating the molecule XML files
	 *
	 * @return string Content constituting the illustration log's constituents
	 */
	public function createIllustrationLog($moleculeXml, $productInfo, $code, $zipDate, $moleculeIndex=0) {
		$molecule = Molecule::allForCurrentProduct()
				->where('code', '=', $code)
				->get()
				->first();

		if(!$molecule) {
			return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
        }
        
        if($productInfo['doctype'] == 'xhtml') {
            $doc = new \DOMDocument();
            $xsl = new \XSLTProcessor();

            $doc->loadXML($moleculeXml);

            $xsldoc = new \DOMDocument();

            $xsldoc->load('../app/Http/Controllers/doctype/' . $productInfo['doctype'] .'/export_multimedia.xslt');

            $xsl->importStyleSheet($xsldoc);

            $parameters = array(
                'output_format' => 'dataset',
                'exportsequence' => $moleculeIndex,
            );

            //$parameters = array_merge($parameters, $basepath);

            $xsl->setParameter('', $parameters);

            $figureLog = $xsl->transformToXML($doc);

        } else {

            //Top Header material for tab delimited illustration log download. static content added to log.
            $metaheader_default = <<<METAHEADER
Illustration Processing Control Sheet\t\t\t\t\t\t\t\t\t\t\t
Author:\t{$productInfo['author']}\t\t\t\t\t\t\t\t\t\t\tISBN:\t{$productInfo['isbn']}\t\t\t\t
Title:\t{$productInfo['title']}\t\t\t\t\t\t\t\t\t\t\tEdition:\t{$productInfo['edition']}\t\t\t\t
Processor:\t{$productInfo['cds']['firstname']} {$productInfo['cds']['lastname']}\t\t\t\t\t\t\t\t\t\t\tChapter:\t{$code}\t\t\t\t
Phone/Email:\t{$productInfo['cds']['phone']}/{$productInfo['cds']['email']}\t\t\t\t\t\t\t\t\t\t\tDate:\t{$zipDate}\t\t\t\t
Figure Number\tPieces (No.)\tDigital (Y/N)\tTo Come\t Previous edition fig #\tLegend\t Borrowed from other Elsevier sources (author(s), title, ed, fig #)\tLong credit line\tDigital file name (include disc number if multiple discs)\tFINAL FIG FILE NAME\t 1/C HT\t 2/C HT\t 4/C HT\t 1/C LD\t 2/C LD\t 4/C LD\tArt category\tArt point of contact\t Comments\n
METAHEADER;

            $figureLog = $this->addFigureLog($moleculeXml, $metaheader_default);

            //change encoding so that Microsoft Excel displays UTF* properly
            $figureLog = mb_convert_encoding($figureLog, 'UTF-16LE', 'UTF-8');
        }

		return $figureLog;
	}



    /**
     * Gets a list of properly sorted atoms that are ready for publication.
     * param array $orderedIds from molecule export(), the array of publishable ids for the chapter, in the current sort
     * order (sort based on current atom in any status but ids are most current and ready to publish atoms)
     *
     * @param integer[] $orderedIds Sorted atom IDs
     *
     * @return object[]
     */
    protected function _getMysortedPublishedAtoms($orderedIds) {
        $atoms = [];

        foreach($orderedIds as $orderedId) {
            $versions = Atom::allForCurrentProduct()
                    ->where('id', '=', $orderedId)
                    ->get();
            foreach($versions as $version) {
                $atoms[] = $version;
            }
        }

        return $atoms;
    }

    /**
     * Gets a list of atoms that are ready for publication.
     *
     * @return object[]
     */
    protected function _getMyPublishedAtoms() {
        $atoms = [];

        $publishedStatuses = Status::getReadyForPublicationStatuses($this->product_id);
        $trashedStatuses = Status::getTrashedStatuses($this->product_id);

        $entityIds = Atom::allForProduct($this->product_id)
                ->select(DB::raw('DISTINCT entity_id'))
                ->where('molecule_code', '=', $this->code)
                ->get()
                ->pluck('entity_id')
                ->all();
        foreach($entityIds as $entityId) {
            $versions = Atom::allForCurrentProduct()
                    ->where('entity_id', '=', $entityId)
                    ->orderBy('alpha_title', 'DESC')
                    ->get();
            foreach($versions as $version) {
                if(in_array($version->status_id, $publishedStatuses)) {
                    $atoms[] = $version;
                    break;
                }
                else if(in_array($version->status_id, $trashedStatuses)) {
                    break;      //a trashed version takes precedence over previously published versions
                }
            }
        }

        return $atoms;
    }

    /**
     * Check if one or more molecules are locked.
     *
     * @param ?string|string[] $codes The molecule code(s) to check
     * @param integer $productId Limit to this product
     *
     * @return object[] An associative array containing locked molecules
     */
    public static function locked($codes, $productId) {
        if($codes === null) {
            return [];
        }

        $codes = is_array($codes) ? $codes : [$codes];
        $codes = array_unique($codes);
        $locks = array_fill_keys($codes, false);
        $molecules = self::allForProduct($productId)
                ->where('locked', '=', true)
                ->whereIn('code', $codes)->get();
        foreach($molecules as $molecule) {
            $locks[$molecule->code] = $molecule;
        }

        return array_filter($locks);
    }

    /**
     * Automatically sort a molecule's atoms.
     *
     * @api
     *
     * @param integer $productId The current product's id
     * @param string $code The molecule code
     */
    public static function autoSort($productId, $code) {
        \DB::transaction(function () use ($productId, $code) {
            $atoms = Atom::allForCurrentProduct()
                    ->where('molecule_code', '=', $code)
                    ->where('product_id', '=', $productId)
                    ->whereIn('id', function ($q) {
                        Atom::buildLatestIDQuery(null, $q);
                    })
                    ->get();

            $keyedAtoms = [];
            foreach($atoms as $atom) {
                $key = strtolower($atom->alpha_title);

                //ensure key is unique
                while(isset($keyedAtoms[$key])) {
                    $key .= '_z';
                }

                $keyedAtoms[$key] = $atom;
            }
            ksort($keyedAtoms);

            $i = -1;
            foreach($keyedAtoms as $atom) {
                $atom->sort = ++$i;
                $atom->simpleSave();
            }
        });
    }

    /**
     * Count the atoms that are currently eligible for export.
     *
     * @param ?integer $statusId (optional) Only count atoms with this status
     *
     * @return integer
     */
    public function countExportable($statusId = null) {
        return sizeof($this->_getExportSortOrder($statusId));
    }

    /**
     * Get the molecule's ordered atom IDs for use in export. This grabs the current atoms sort order and integrates it
     * with the Id for the publishable version of the atom, producing an array of Ready for Production atom Ids in the
     * current desired sort order.
     *
     * @param ?integer $statusId (optional) Only export atoms with this status
     *
     * @return string[]
     */
    protected function _getExportSortOrder($statusId = null) {
        $values = [
            'moleculeCode'  => $this->code,
            'productId'     => (int)$this->product_id,
            'statusId'      => $statusId
        ];

        //output all that has status as 'ready for publication' (curently or historically), excluding deactivated (status_id !~ '300$')
        $sql = "SELECT a.id AS pubid, a.entity_id AS pubentityid, b.id AS currentid, b.entity_id AS currententityid,
                    b.sort AS currentsort
                FROM (
                    SELECT * FROM atoms
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM atoms
                        WHERE product_id=:productId AND status_id =:statusId
                        GROUP BY entity_id
                    ) AND molecule_code=:moleculeCode AND deleted_at IS NULL ORDER BY sort ASC
                ) a
                INNER JOIN (
                    SELECT * FROM atoms
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM atoms
                        WHERE product_id=:productId
                        GROUP BY entity_id
                    ) AND molecule_code=:moleculeCode AND deleted_at IS NULL AND cast (status_id as text) !~ '300$' ORDER BY sort ASC
                ) b ON a.entity_id=b.entity_id
                WHERE a.product_id=:productId AND b.product_id=:productId;";

        $atoms = DB::select(DB::raw($sql), $values);

        $atomIds = array_map(
            function ($a) {
                return $a->pubid;
            },
            $atoms
        );

        return $atomIds;
    }

    /**
     * Get the molecule's ordered atom IDs.
     *
     * @param integer $productId Limit to this product
     * @param ?integer $statusId (optional) Only export atoms with this status
     *
     * @return string[]
     */
    protected function _getSortOrder($productId, $statusId = null) {
        $atoms = Atom::allForProduct($productId)
                ->where('molecule_code', '=', $this->code)
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->orderBy('sort', 'ASC')
                ->get();

        return $atoms->pluck('id')->all();
    }

    public function removeFullCredit($xml){
        $xmlNoFullcredit = preg_replace('/<fullcredit>[^<]*<\/fullcredit>/', '', $xml);
        $xmlNoFullcredit = preg_replace('/<fullcredit\/>/', '', $xmlNoFullcredit);
        return $xmlNoFullcredit;
    }
}
