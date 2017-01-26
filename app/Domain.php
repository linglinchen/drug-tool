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
                ->where('molecule_code', '=', $molecule['code'])
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->orderBy('sort', 'ASC')
                ->get();
        Comment::addSummaries($atoms, $productId);

        foreach($atoms as $key => $atom) {
            $atom->addAssignments($productId);
            $atom = $atom->toArray();
            unset($atom['xml']);
            $atoms[$key] = $atom;
        }

        $molecule['atoms'] = $atoms;

        return $molecule;
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
