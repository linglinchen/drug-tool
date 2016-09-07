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
     * Export a molecule to XML. Only blessed atoms are included.
     *
     * @param string $code The molecule's code
     *
     * @returns string
     */
    public static function export($code) {
        $atoms = Atom::where('molecule_code', '=', $code)
                ->whereIn('id', Atom::latestIds())
                ->orderBy('sort', 'ASC')
                ->get();

        $xml = '<alpha letter="">' . "\n";
        foreach($atoms as $atom) {
            $atomXml = $atom->export();
            $atomXml = str_replace("\n", "\n\t", $atomXml);      //indent the atom
            $xml .= $atomXml . "\n";
        }
        $xml .= '</alpha>';

        return $xml;
    }
}
