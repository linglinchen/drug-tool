<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\AppModel;
use App\Atom;
use App\Assignment;
use App\Comment;
use App\Status;

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
                ->where('molecule_code', '=', $molecule['code'])
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->orderBy('sort', 'ASC')
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
                $xmlObject = simplexml_load_string($commentXml);
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
	 * @return string
	 */
	public function export($statusId = null, $doctype='drug', $withFigures=0) {
		//Below diverts to the separate 'getExportSortOrder' so that only Ready to publish atoms are in sort. Plain
		//'getSortOrder' always chooses current atoms, so this separate sort order is needed for the export.
        ini_set('memory_limit', '1280M');
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
			],
			'drug' => [
				'root' => 'alpha',
				'attributes' => [
					'letter' => $this->code,
				],
			],
			'question' => [
				'root' => 'chapter',
				'attributes' => [
					'id' => 'c' . $this->code,
					'number' => $this->code,
				],
			],

		];

		$xml = "\t" . '<' . $xmlMolecule[$doctype]['root'];
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

		return $xml;
	}

    /**
     * Take the xml from above and reduce it to a log of figures.
     *
     * @return string A CSV string
     */
    public function addFigureLog($moleculeXml, $metaheader) {
        $ob = simplexml_load_string($moleculeXml);
        $figureNodes = $ob->$moleculeXml->xpath('//component[@type="figure"]');

        if($figureNodes) {
            $figureRows =" \t";

            foreach($figureNodes as $figureNode) {
                $closestEntryNodes = $figureNode->xpath('ancestor::entry[1]/headw//text()');
                $closestEntryNodes = json_encode($closestEntryNodes);
                $closestEntryNodes = (array)json_decode($closestEntryNodes, true);
                $closestEntry = '';
                foreach($closestEntryNodes as $closestEntryNode) {
                    if(isset($closestEntryNode[0])) {
                        $closestEntry = $closestEntryNode[0];
                    }
                }

                $mainEntryNodes = $figureNode->xpath('ancestor::entry[parent::alpha]/headw//text()');
                $mainEntryNodes = json_encode($mainEntryNodes);
                $mainEntryNodes = (array)json_decode($mainEntryNodes, true);
                $mainEntry = '';
                foreach($mainEntryNodes as $mainEntryNode) {
                    if(isset($mainEntryNode[0])) {
                        $mainEntry = $mainEntryNode[0];
                    }
                }

                $term = $closestEntry == $mainEntry ? $mainEntry : $mainEntry . '/' . $closestEntry;

                $figureNode = json_encode($figureNode);
                $figureNode = json_decode($figureNode, true);

                $sourceItem = isset($figureNode['credit'])? implode(' ', (array)$figureNode['credit']) : '';
                $sourceItem = htmlentities($sourceItem);

                $sourceItemFull = isset($figureNode['fullcredit'])? implode(' ', (array)$figureNode['fullcredit']) : '';
                $sourceItemFull = htmlentities($sourceItemFull);

                $caption = isset($figureNode['caption']) ? implode(' ', (array)$figureNode['caption']) : '';
                $caption = htmlentities($caption);

                $availability = isset($figureNode['@attributes']['availability']) ?
                        $figureNode['@attributes']['availability'] :
                        '';
                if($availability == 'electronic') {
                    $availability = 'online only';
                }
                else if($availability == 'print') {
                    $availability = 'print only';
                }
                else if($availability == 'both') {
                    $availablity = 'print and online';
                }
                else {
                    $availablity = '';
                }

                if(isset($figureNode['@attributes']) && isset($figureNode['@attributes']['id'])) {
                    if(isset($figureNode['file'])) {
                        if(count($figureNode['file']) > 1) {
                            foreach($figureNode['file'] as $file) {
                                if(isset($file['@attributes']) && isset($file['@attributes']['src'])) {
                                    $figureRows .= "\n" . $term . "\t\tYes\t\t" . $figureNode['@attributes']['id'] .
                                            "\t" . $caption ."\t". $sourceItem . "\t" . $sourceItemFull. "\t". $file['@attributes']['src'] .
                                            "\t\t\t\t\t\t\t\t\t". "Comp\t".$availability.' ';
                                }
                                else if(isset($file['src'])) {  //for situation when abdomen: [0]=>
                                    $figureRows .= "\n" . $term."\t\tYes\t\t" . $figureNode['@attributes']['id'] .
                                            "\t" . $caption ."\t". $sourceItem."\t" . $sourceItemFull. "\t". $file['src'] . "\t\t\t\t\t\t\t\t\t" . "Comp\t" .
                                            $availability.' ';
                                }

                            }
                        }
                        else if(
                            isset($figureNode['file']['@attributes']) &&
                            isset($figureNode['file']['@attributes']['src'])
                        ) {
                            $figureRows .= "\n" . $term . "\t\tYes\t\t" . $figureNode['@attributes']['id'] . "\t" . $caption ."\t".
                                    $sourceItem . "\t" . $sourceItemFull. "\t". $figureNode['file']['@attributes']['src'] .
                                    "\t\t\t\t\t\t\t\t\t" . "Comp\t" . $availability . ' ';
                        }
                    }
                    else if(
                        isset($figureNode['p']) && isset($figureNode['p']['@attributes']) &&
                        isset($figureNode['p']['@attributes']['src_stub'])
                    ) {
                        //img situation: [p]->[src_stub] is equal to [file][src]
                        $figureRows .= "\n" . $term . "\t\tYes\t\t" . $figureNode['@attributes']['id'] . "\t" . $caption ."\t".
                                $sourceItem . "\t" . $sourceItemFull. "\t". $figureNode['p']['@attributes']['src_stub'] .
                                "\t\t\t\t\t\t\t\t\t" . "Comp\t" . $availability . ' ';
                    }
                }
            }

            $figureLogRows = $metaheader . $figureRows;
        }
        else {
            $figureLogRows = $metaheader . 'No figures in this Chapter';
        }

        return $figureLogRows;
    }

    /**
     * Take the xml from above and return an array of image file names
     *
     * @return array
     */
    public function getImageFileName($moleculeXml) {
        $ob = simplexml_load_string($moleculeXml);
        $figureNodes = $ob->$moleculeXml->xpath('//component[@type="figure"]');
        $imageFiles = [];

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

        return $imageFiles;
    }


	/**
	 * Get illustration files and add to the export zip
	 *
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param object $zip The zip object into which to add the illustrations
	 * @param array $productInfo Information about the current product
	 * @param array $code The molecule code being written
	 *
	 * @return boolean Also modifies provided $zip
	 */
	public function getIllustrations($moleculeXml, $zip, $productInfo, $code) {
		//TODO: pick these up from configuration
		$s3UrlDev = 'https://s3.amazonaws.com/metis-imageserver-dev.elseviermultimedia.us';
		$s3UrlProd = 'https://s3.amazonaws.com/metis-imageserver.elseviermultimedia.us';
		//these must be in order of preference
		$imageExtensions = ['eps', 'tif', 'jpg',];

		$molecule = Molecule::allForCurrentProduct()
				->where('code', '=', $code)
				->get()
				->first();

		if(!$molecule) {
			return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
		}

		$imageFiles = $this->getImageFileName($moleculeXml);
		foreach($imageFiles as $imageFile) {
			$imageFound = false;
			
			//suggested image
			if(substr($imageFile, 0, 9) == 'suggested') {
				$imageDir = '';

			//legacy image
			} else {
				$imageDir = $productInfo['isbn_legacy'] . "/";
			}

			$imagePath = $s3UrlProd . "/" . $imageDir . $imageFile;

			foreach($imageExtensions as $imageExtension) {
				//NOTE: we're ignoring what should be 404 errors because we _expect_ failures and want to quickly skip to next attempt
				if(!$image = @file_get_contents($imagePath . "." . $imageExtension)) {
					if(!$image = @file_get_contents($imagePath . "." . strtoupper($imageExtension))) {
						//not found, check next extension
						continue;
					}
					//found CAPITAL EXTENSION (ick)
					$imageFound = true;
					break;
				}
				//found default extension
				$imageFound = true;
				break;

			}

			//incidentally this will rename everything to use a lowercase extension
			if($imageFound === true && $image) {
				$zip->addFromString($imageFile . "." . $imageExtension, $image);

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
	 *
	 * @return boolean Also modifies provided $zip
	 */
	public function getIllustrationLog($moleculeXml, $zip, $productInfo, $code, $zipDate) {
		$figureLog = $this->createIllustrationLog($moleculeXml, $productInfo, $code, $zipDate);

		$zip->addFromString('IllustrationLog_' . $code . '.tsv' ,  $figureLog);

		return true;
	}


	/**
	 * Create header and fetch content for illustration log
	 *
	 * @param string $moleculeXml The current molecule's XML in which the illustrations are to be found
	 * @param array $productInfo Information about the current product
	 * @param array $code The molecule code being written
	 * @param array $zipDate The date on which this zip is being created and at which time the illustration log was generated
	 *
	 * @return string Content constituting the illustration log's constituents
	 */
	public function createIllustrationLog($moleculeXml, $productInfo, $code, $zipDate) {
		$molecule = Molecule::allForCurrentProduct()
				->where('code', '=', $code)
				->get()
				->first();

		if(!$molecule) {
			return ApiError::buildResponse(Response::HTTP_NOT_FOUND, 'The requested molecule could not be found.');
		}

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
            'productId'     => (int)$this->product_id
        ];

        //output all status for Sarah Vora
        $sql = "SELECT a.id AS pubid, a.entity_id AS pubentityid, b.id AS currentid, b.entity_id AS currententityid,
                    b.sort AS currentsort
                FROM (
                    SELECT * FROM atoms
                    WHERE id IN (
                        SELECT MAX(id)
                        FROM atoms
                        GROUP BY entity_id
                    ) AND molecule_code=:moleculeCode AND deleted_at IS NULL ORDER BY sort ASC
                ) a
                INNER JOIN (
                    SELECT * FROM atoms
                    WHERE id IN (
                        SELECT MAX(id) FROM atoms GROUP BY entity_id
                    ) AND molecule_code=:moleculeCode AND deleted_at IS NULL ORDER BY sort ASC
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
