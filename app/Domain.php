<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\AppModel;
use App\Atom;
use App\Comment;
use App\Status;

class Domain extends AppModel {
    use SoftDeletes;

    protected $table = 'domains';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];

    /*
     * Returns all domain titles as an associative array.
     * code => title
     *
     * @param integer $productId Limit to this product
     *
     * @return string[]
     */
    public static function getLookups($productId) {
        $output = [];
        $domains = self::allForProduct($productId);
        foreach($domains as $domain) {
            $output[$domain['code']] = $domain['title'];
        }

        return $output;
    }

    /**
     * Add atoms to the domain.
     *
     * @param integer $productId Limit to this product
     *
     * @param mixed[] $domain The domain
     */
    public static function addAtoms($domain, $productId) {
        $atoms = Atom::allForProduct($productId)
                ->where('domain_code', '=', $domain['code'])
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->orderBy('alpha_title', 'ASC')
                ->get();

        // Comment::addSummaries($atoms, $productId);
        // foreach ($atoms as $atom){
        //     $atom->addCommentSuggestions($atom['entity_id']);
        // }
        // foreach($atoms as $key => $atom) {
        //     $atom->addAssignments($productId);
        //     $atom = $atom->toArray();
        //     unset($atom['xml']);
        //     $atoms[$key] = $atom;
        // }

        //get assignments for each atom
        //join atoms and assignments tables, atom will be the lastest version, not deleted
        $sql_assignment =
            "SELECT ass.*
            FROM atoms a
            JOIN assignments ass ON a.entity_id = ass.atom_entity_id
            WHERE product_id = " . $productId . "
                and domain_code = '" . $domain['code'] . "'
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
                and domain_code = '" . $domain['code'] . "'
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

        $domain['atoms'] = $atoms;

        return $domain;
    }

    /**
     * Export the molecule to XML. Takes the LATEST VERSION of each atom that matches the statusId (if passed).
     *
     * @returns string
     */
    public function export($statusId = null) {  //not ready
        $orderedIds = $this->_getSortOrder($this->product_id, $statusId);

        $unorderedAtoms = $this->_getMyPublishedAtoms();

        //postgres doesn't support ORDER BY FIELD, so...
        $atoms = array_flip($orderedIds);
        foreach($unorderedAtoms as $atom) {
            $atoms[$atom->id] = $atom;
        }
        $atoms = array_filter($atoms, function ($element) {
            return !is_numeric($element);       //remove atoms that have never been published
        });

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
     * Gets a list of atoms that are ready for publication.
     *
     * @return object[]
     */
    protected function _getMyPublishedAtoms() {  //not ready
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
                    ->orderBy('id', 'DESC')
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
    public static function locked($codes, $productId) {  //not ready
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
     * Get the molecule's ordered atom IDs.
     *
     * @param integer $productId Limit to this product
     * @param ?integer $statusId (optional) Only export atoms with this status
     *
     * @return string[]
     */
    protected function _getSortOrder($productId, $statusId = null) {  //not ready
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
