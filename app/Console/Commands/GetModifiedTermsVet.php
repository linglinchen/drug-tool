<?php

/* search for atoms that has been edited
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Product;
use App\Status;

class GetModifiedTermsVet extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:modifiedTermsVet {productId}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command searches for atoms that xml has not been changed ,  e.g. get:modifiedTermsVet 3';

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

        self::_getModified($productId);
    }

    public static function _getModified($productId) {

         // getting the original imported atoms
         $sql = "SELECT id, entity_id
            FROM atoms
            WHERE 
		        product_id = $productId
                and id in 
                (
                    SELECT MIN(id)
                    FROM atoms 
                    WHERE product_id = $productId
                    GROUP BY entity_id
                )
            AND created_at < '2017-05-26 20:20:20'
            order by molecule_code, sort
        ";
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);  //convert object to array
        $file = fopen("../modifiedTermsVet.csv", "w") or die("Unable to open file!");
        $count = 0;
        foreach($atomsArray as $atom) {
            $atomModel = Atom::find($atom['id']);
            //get all versions
            $sql1 = "SELECT * 
                    FROM atoms 
                    WHERE product_id = $productId
                        and entity_id = '$atomModel->entity_id'
                    ORDER by molecule_code, sort";
            $atomVersions = DB::select($sql1);
            if ($atomVersions){
                $atomVersionsArray = json_decode(json_encode($atomVersions), true);
              }  //convert object to array
            if (sizeof($atomVersionsArray) > 1) {
                $count ++;
                fwrite($file, "$atomModel->alpha_title, $atomModel->entity_id, $atomModel->molecule_code\n");
                echo "$atomModel->entity_id\t$count\n";
            }
        }
        fclose($file);
    }
}