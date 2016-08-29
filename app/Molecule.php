<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use App\AppModel;
use App\Atom;

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
        $currentAtomIds = Atom::latestIds();
        $atoms = Atom::where('moleculeCode', '=', $molecule['code'])
                ->whereIn('id', $currentAtomIds)
                ->get();

        foreach($atoms as $key => $atom) {
            $atom->addAssignments();
            $atom = $atom->toArray();
            unset($atom['xml']);
            $atoms[$key] = $atom;
        }

        $molecule['atoms'] = $atoms;

        return $molecule;
    }
}
