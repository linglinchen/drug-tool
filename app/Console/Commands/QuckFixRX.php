<?php

/* Change RX and rx to be Rx globally

	Expected results from sample data:
	42 ignored (eg, firmly, xmlns:mml)
	16 mL ignored
	2543 changed
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class QuickFixRX extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:rx';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command changes RX and rx to Rx globally.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        self::fixLowercasedRX();
    }

    public static function fixLowercasedRX() {
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->get();
        $total_replaced = 0;
        $total_replaced_atoms = 0;
		$searchreplace = array(
			"/\bRX\b/Si" => "Rx",
		);


        foreach($atoms as $atom) {
            $newXml = preg_replace(array_keys($searchreplace), array_values($searchreplace), $atom->xml, -1, $count);
            $total_replaced += $count;
            if($newXml != $atom->xml) {
                if ($atom->id === 1991){
                    echo $newXml;
                    exit;
                }
                $newAtom = $atom->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
               // $newAtom->save();
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