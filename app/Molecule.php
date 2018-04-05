<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\AppModel;
use App\Atom;
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
                    Atom::buildLegacyLatestIDQuery(null, $q);
                })
                ->orderBy('sort', 'ASC')
                ->get();
        Comment::addSummaries($atoms, $productId);

        foreach($atoms as $key => $atom) {
            $atom->addAssignments($productId);
            $atom->addDomains($productId);
            $atom->addCommentSuggestions($atom['entity_id']);
            $atom = $atom->toArray();
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
    public function export($statusId = null) {

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

        $moleculecode=$this->code;

        $sql="select a.id as pubid, a.entity_id as pubentityid, b.id as currentid, b.entity_id as currententityid, b.sort as currentsort from (
        select * from atoms where id in (select MAX(id) from atoms where status_id=$statusId group by entity_id) and molecule_code='".$moleculecode."' and deleted_at is null order by sort asc ) a
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
