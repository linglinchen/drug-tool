<?php

/* search for atoms that has been edited but status remains 'ready for publication'
 * change those atoms' status to be 'development'
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Product;
use App\Status;

class QuickFixStatus extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:status {productId} {edition?}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command searches for atoms that has been edited but status remains "ready for publication", then change those atoms\' status to be "development".  e.g. quickfix:status 4; or quickfix:status 1 2019';

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

        $edition = (string)$this->argument('edition');
        if (!$edition){
            $edition = NULL;
        }
        self::_fixStatus($productId, $edition);
    }

    public static function _fixStatus($productId, $edition) {
         $pubStatusId = Status::getReadyForPublicationStatuses($productId)[0];
         $devStatusId = Status::getDevStatusId($productId)->id;
         $editionSql = $edition == NULL ?  "IS NULL" : "= $edition";

         // make sure it's not system change (modified_by is null), not by TNQ (modified_by = 95)
         $sql = "SELECT MAX(id) as id, entity_id
            FROM atoms
            WHERE 
		        product_id = $productId
                and edition $editionSql
                and modified_by IS NOT NULL
                and modified_by != 95
                and entity_id in 
                (
                    SELECT entity_id 
                    FROM atoms 
                    WHERE product_id = $productId
                        and edition $editionSql
                        and status_id = $pubStatusId
                    GROUP BY entity_id
                    HAVING count(*) > 1
                )
            GROUP BY entity_id
        ";
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);  //convert object to array
        $totalDetectedAtoms = sizeof($atomsArray);
        $changedAtoms = 0;
        foreach($atomsArray as $atom) {
            $atomModel = Atom::find($atom['id']);
            if ($atomModel->status_id == $pubStatusId){
                $newAtom = $atomModel->replicate();
                $newAtom->modified_by = null;
                $timestamp = $newAtom->freshTimestampString();
                $newAtom->created_at = $timestamp;
                $newAtom->updated_at = $timestamp;
                $newAtom->status_id = $devStatusId;
                $changedAtoms++;
                echo $newAtom->alpha_title."\n";
                $newAtom->save();
            }
        }

        /* output messages */
        echo 'affected Atoms: '.$totalDetectedAtoms."\n";
        echo 'changed Atoms: '.$changedAtoms."\n";
    }
}