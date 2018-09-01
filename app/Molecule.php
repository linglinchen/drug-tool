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
        $atoms = Atom::allForProduct($productId)
                ->where('molecule_code', '=', $molecule['code'])
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->orderBy('sort', 'ASC')
                ->get();

        //get assignments for each atom
        $sql_assignment =
            "SELECT ass.*
            FROM atoms a
            join assignments ass on a.entity_id = ass.atom_entity_id
            WHERE product_id = ".$productId."
                and molecule_code = '".$molecule['code']."'
                and a.id in
                    (SELECT id FROM atoms WHERE id in
                        (SELECT MAX(id) FROM atoms where product_id=".$productId." GROUP BY entity_id)
                    and deleted_at IS NULL)
            ORDER BY sort ASC, ass.id ASC"; //join atoms and assignments tables, atom will be the lastest version, not deleted

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
            join comments c on a.entity_id = c.atom_entity_id
            WHERE product_id = ".$productId."
                and molecule_code = '".$molecule['code']."'
                and a.id in
                    (SELECT id FROM atoms WHERE id in
                        (SELECT MAX(id) FROM atoms where product_id=".$productId." GROUP BY entity_id)
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
                $figureFile = $figureFileObj ? json_decode(json_encode($figureFileObj), true)['@attributes']['src'] : '';

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
            $atom['assignments'] = array_key_exists($atom['entity_id'], $assignmentsByAtom) ? $assignmentsByAtom[$atom['entity_id']] : [];
            $atom['suggestedFigures'] = array_key_exists($atom['entity_id'], $commentsFigure) ? $commentsFigure[$atom['entity_id']] : [];
            $atom['commentSummary'] = array_key_exists($atom['entity_id'], $commentSummaries) ? $commentSummaries[$atom['entity_id']] : null;
            unset($atom['xml']);
            $atoms[$key] = $atom;
        }

        $molecule['atoms'] = $atoms;
        return $molecule;
    }

    /**
     * Export the molecule to XML. Takes the LATEST "Ready to Publish" VERSION of each atom that matches the statusId (if passed).
     *
     * @returns string
     */
    public function export($statusId = null, $withFigures=0) {
        //Below diverts to the separate 'getExportSortOrder' so that only Ready to publish atoms are in sort. Plain 'getSortOrder' always chooses current atoms, so this separate sort order is needed for the export. Changed January 2018 - JZ.
        $orderedIds = $this->_getExportSortOrder($this->product_id, $statusId->id);

        $orderedAtoms = $this->_getMysortedPublishedAtoms($orderedIds);

        $atoms = $orderedAtoms;

        $xml = "\t" . '<alpha letter="' . $this->code . '">' . "\n";
        foreach($atoms as $atom) {
            $atomXml = $atom->export();
            $atomXml = "\t\t" . str_replace("\n", "\n\t\t", $atomXml);      //indent the atom
            $xml .= $atomXml . "\n";
        }
        $xml .= "\t" . '</alpha>' . "\n";

        return $xml;
    }
    /**
     * take the xml from above and reduce it to a log of figures.
     *
     * @returns csv
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

                $term = $closestEntry == $mainEntry ? $mainEntry : $mainEntry.'/'.$closestEntry;

                $figureNode = json_encode($figureNode);
                $figureNode = json_decode($figureNode, true);

                $sourceItem = isset($figureNode['credit'])? $figureNode['credit']: '';
                $sourceItem =htmlentities($sourceItem);

                $availability = isset($figureNode['@attributes']['availability']) ? $figureNode['@attributes']['availability'] : '';
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
                                    $figureRows .="\n".$term."\t\tYes\t\t" .$figureNode['@attributes']['id']."\t".$sourceItem."\t".$file['@attributes']['src']."\t\t\t\t\t\t\t\t\t". "Comp\t".$availability.' ';
                                }
                                else if(isset($file['src'])) {  //for situation when abdomen: [0]=>
                                    $figureRows .="\n".$term."\t\tYes\t\t" .$figureNode['@attributes']['id']."\t".$sourceItem."\t".$file['src']."\t\t\t\t\t\t\t\t\t". "Comp\t".$availability.' ';
                                }

                            }
                        }
                        else if(isset($figureNode['file']['@attributes']) && isset($figureNode['file']['@attributes']['src'])) {
                            $figureRows .="\n".$term."\t\tYes\t\t" .$figureNode['@attributes']['id']."\t".$sourceItem."\t".$figureNode['file']['@attributes']['src']."\t\t\t\t\t\t\t\t\t". "Comp\t".$availability.' ';
                        }
                    }
                    else if(isset($figureNode['p']) && isset($figureNode['p']['@attributes']) && isset($figureNode['p']['@attributes']['src_stub'])) {
                        //img situation: [p]->[src_stub] is equal to [file][src]
                        $figureRows .="\n".$term."\t\tYes\t\t" .$figureNode['@attributes']['id']."\t".$sourceItem."\t".$figureNode['p']['@attributes']['src_stub']."\t\t\t\t\t\t\t\t\t". "Comp\t".$availability.' ';
                    }
                }
            }

            $figureLogRows = $metaheader . $figureRows;
        }
        else {
            $figureLogRows = $metaheader . 'No figures in this Chapter';
        }

         return  $figureLogRows;
    }


    /**
     * take the xml from above and return an array of image file names
     *
     * @returns array
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
                        else if(isset($figureNode['file']['@attributes']) && isset($figureNode['file']['@attributes']['src'])) {
                            $imageFiles[] = $figureNode['file']['@attributes']['src'];
                        }
                    }
                    else if(isset($figureNode['p']) && isset($figureNode['p']['@attributes']) && isset($figureNode['p']['@attributes']['src_stub'])) {
                        //img situation: [p]->[src_stub] is equal to [file][src]
                        $imageFiles[] = $figureNode['p']['@attributes']['src_stub'];
                    }
                }
            }
        }
        return  $imageFiles;
    }

    /**
     * Gets a list of properly sorted atoms that are ready for publication.
     * param array $orderedIds from molecule export(), the array of publishable ids for the chapter, in the current sort order (sort based on current atom in any status but ids are most current and ready to publish atoms)
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
     * Get the molecule's ordered atom IDs for use in export. This grabs the current atoms sort order and integrates it with the Id for the publishable version of the atom, producing an array of Ready for Production atom Ids in the current desired sort order.
     *
     * @param integer $productId Limit to this product
     * @param ?integer $statusId (optional) Only export atoms with this status
     *
     * @return string[]
     */
    protected function _getExportSortOrder($productId, $statusId = null) {
        $moleculecode = $this->code;

        //output all status for Sarah Vora
        $sql = "select a.id as pubid, a.entity_id as pubentityid, b.id as currentid, b.entity_id as currententityid, b.sort as currentsort from (
        select * from atoms where id in (select MAX(id) from atoms group by entity_id) and molecule_code='".$moleculecode."' and deleted_at is null order by sort asc ) a
        inner join  (
        select * from atoms where id in (select MAX(id) from atoms group by entity_id) and molecule_code='".$moleculecode."' and deleted_at is null order by sort asc ) b on a.entity_id=b.entity_id
                                where a.product_id=$productId and b.product_id=$productId;";

        $atoms = DB::select($sql);

        $atomIds = array_map(function($a) { return $a->pubid; }, $atoms);

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
}
