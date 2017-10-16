<?php

/*
 * Create a list of atom and it's domains (including main term domain and subterm domains)
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Product;

class CreateAtomDomainsList extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createatomdomainslist {productId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command list for atoms and all their domains, e.g. php artisan createatomdomainslist 5';

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

        self::_createAtomDomainsList($productId);
    }

    public static function _createAtomDomainsList($productId) {
         $sql = "select id, alpha_title, xml from atoms 
            where id IN (" . Atom::buildLatestIDQuery()->toSql() . ")
            AND product_id = ".$productId;

        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);
        foreach($atomsArray as $atom) {
            preg_match_all('/<category[^>]*>(.*)<\/category>/Si', $atom['xml'], $matches);
            if ($matches[1]){
                
                $uniques = array_unique($matches[1]);
                //sort($uniques);
                foreach ($uniques as $unique){
                    if (strlen(trim($unique)) > 0){
                        echo $atom['alpha_title']."\t".$unique."\n";
                    }
                }
            }
        }
    }
}