<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class QuickFix_ml extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix_ml';

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
		$searchreplace = array(
			"/([^A-Za-z])ml([^A-Za-z])/mu" => "$1mL$2", #camelcase milliliters
		);


        foreach($atoms as $atom) {
            /* EXPECTED:
				42 ignored (eg, firmly, xmlns:mml)
				16 mL ignored
				2543 changed
			*/


            /*if(preg_match("/([^A-Za-z])mL([^A-Za-z])/", $atom->xml)) {
            	if($atom->title == 'dulaglutide (Rx)') { echo $atom->xml . "\n"; }
            	$total_replaced++;
            	//echo $atom->title . "\n";
            }*/

            $newXml = preg_replace(array_keys($searchreplace), array_values($searchreplace), $atom->xml, -1, $count);
            $total_replaced += $count;
            if($newXml != $atom->xml) {
                $newAtom = $atom->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
                $newAtom->save();
            }
        }

        echo 'Total replacements: ' . $total_replaced . "\n";

    }
}
