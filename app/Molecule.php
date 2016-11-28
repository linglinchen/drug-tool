<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\AppModel;
use App\Atom;
use App\Comment;

class Molecule extends AppModel {
    use SoftDeletes;

    protected $table = 'molecules';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];

    /*
     * Returns all molecule titles as an associative array.
     * code => title
     */
    public static function getLookups() {
    	$output = [];
    	$molecules = self::all();
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
        $atoms = Atom::where('molecule_code', '=', $molecule['code'])
                ->where('product_id', '=', $productId)
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
     * @param integer $productId Limit to this product
     * @param ?integer $statusId (optional) Only export atoms with this status
     *
     * @returns string
     */
    public function export($productId, $statusId = null) {
        $orderedIds = $this->_getSortOrder($productId, $statusId);

        $unorderedAtoms = Atom::where('molecule_code', '=', $this->code)
                ->where('product_id', '=', $productId)
                ->whereIn('id', function ($q) use ($statusId) {
                    Atom::buildLatestIDQuery($statusId, $q);
                })
                ->get();

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
        $molecules = self::where('locked', '=', true)
                ->where('product_id', '=', $productId)
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
    protected function _getSortOrder($productId, $statusId = null) {
        $atoms = Atom::where('molecule_code', '=', $this->code)
                ->where('product_id', '=', $productId)
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })
                ->orderBy('sort', 'ASC')
                ->get();

        return $atoms->pluck('id')->all();
    }
}
