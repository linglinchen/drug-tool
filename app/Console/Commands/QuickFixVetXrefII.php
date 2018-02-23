<?php

/* trying to add missing xref to the latest version of atoms, based on the oldest version (which have been fixed using a new xml)
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Product;
use App\Status;

class QuickFixVetXrefII extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:vetXrefII {productId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command trys to compare the oldest and latest versions, then add the missing xref to latest version,  e.g. quickfix:vetXrefII 3';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $productId = (int)$this->argument('productId');
        if(!$productId || !Product::find($productId)) {
            throw new \Exception('Invalid product ID.');
        }

        self::_addMissingXref($productId);
    }

    public static function _addMissingXref($productId) {
         // get all atoms
         $sql = "SELECT MAX(id) as id, entity_id
            FROM atoms
            WHERE 
		        product_id = $productId
                and entity_id in 
                (
                    SELECT entity_id 
                    FROM atoms 
                    WHERE product_id = $productId
                    GROUP BY entity_id
                )
        ";
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);  //convert object to array
        $totalDetectedAtoms = 0;

        foreach($atomsArray as $atom) {
            $atomModel = Atom::find($atom['id']);
            //get all versions
            $sql1 = "SELECT * 
                    FROM atoms 
                    WHERE product_id = $productId
                        and entity_id = '$atomModel->entity_id'
                    ORDER by id DESC";
            $atomVersions = DB::select($sql1);
            $atomVersionsArray = json_decode(json_encode($atomVersions), true);  //convert object to array
            $xml_latest = $atomVersionsArray[0]['xml'];
            $xml_second = $atomVersionsArray[1]['xml'];
            $status_latest = $atomVersionsArray[0]['status_id'];
            if (strcmp($xml_latest, $xml_second) == 0 && $status_latest == $devStatusId){//if the most recent version's xml didn't change
                echo $atomModel->entity_id." ".$atomModel->alpha_title."\n";
                $totalDetectedAtoms++;
            }
            
        }

        /* output messages */
        echo 'Detected Atoms: '.$totalDetectedAtoms."\n";
    }
}