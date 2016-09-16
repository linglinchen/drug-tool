<?php

/* ml to mL wherever it thinks this means milliliters. Based on quick fix
	2016-09-16 JWS	original

	Expected results from sample data:
	42 ignored (eg, firmly, xmlns:mml)
	16 mL ignored
	2543 changed
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class QuickFixML extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:ml';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command changes ml to mL wherever it thinks this means milliliters. Based on quick fix.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        self::fixLowercasedML();
    }

    public static function fixLowercasedML() {
        $atoms = Atom::whereIn('id', Atom::latestIDs())->get();
        $total_replaced = 0;
        $total_replaced_atoms = 0;
		$searchreplace = array(
			"/([^A-Za-z])ml([^A-Za-z])/mu" => "$1mL$2", #camelcase milliliters
		);


        foreach($atoms as $atom) {
            $newXml = preg_replace(array_keys($searchreplace), array_values($searchreplace), $atom->xml, -1, $count);
            $total_replaced += $count;
            if($newXml != $atom->xml) {
                $newAtom = $atom->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
                $newAtom->save();
                $total_replaced_atoms++;
            }
        }

        /* output messages */
        echo 'Replacements:' . "\n";
        print_r($searchreplace);
        echo "\n";
        echo 'Total atoms: ' . count($atoms) . "\n";
        echo 'Total updated atoms: ' . $total_replaced_atoms . "\n";
        echo 'Total text replacements: ' . $total_replaced . "\n";

    }
}
