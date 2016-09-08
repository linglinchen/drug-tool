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
     * @param mixed[] $molecule The molecule
     */
    public static function addAtoms($molecule) {
        $atoms = Atom::where('molecule_code', '=', $molecule['code'])
                ->whereIn('id', Atom::latestIds())
                ->orderBy('sort', 'ASC')
                ->get();
        Comment::addSummaries($atoms);

        foreach($atoms as $key => $atom) {
            $atom->addAssignments();
            $atom = $atom->toArray();
            unset($atom['xml']);
            $atoms[$key] = $atom;
        }

        $molecule['atoms'] = $atoms;

        return $molecule;
    }

    /**
     * Export the molecule to XML.
     *
     * @param ?integer $statusId (optional) Only export atoms with this status
     *
     * @returns string
     */
    public function export($statusId = null) {
        $orderedIds = $this->_getSortOrder($statusId);

        $unorderedAtoms = Atom::where('molecule_code', '=', $this->code)
                ->whereIn('id', Atom::latestIds($statusId))
                ->get();

        //postgres doesn't support ORDER BY FIELD, so...
        $atoms = array_flip($orderedIds);
        foreach($unorderedAtoms as $atom) {
            $atoms[$atom->id] = $atom;
        }
        $atoms = array_filter($atoms, function ($element) {
            return !is_numeric($element);       //remove atoms that have never been published
        });

        $xml = '<alpha letter="' . $this->code . '">' . "\n";
        foreach($atoms as $atom) {
            $atomXml = $atom->export();
            $atomXml = "\t" . str_replace("\n", "\n\t", $atomXml);      //indent the atom
            $xml .= $atomXml . "\n";
        }
        $xml .= '</alpha>';

        return $xml;
    }

    /**
     * Get the molecule's ordered atom IDs.
     *
     * @param ?integer $statusId (optional) Only export atoms with this status
     *
     * @return string[]
     */
    protected function _getSortOrder($statusId = null) {
        $atoms = Atom::where('molecule_code', '=', $this->code)
                ->whereIn('id', Atom::latestIds())
                ->orderBy('sort', 'ASC')
                ->get();

        return $atoms->pluck('id')->all();
    }
}
