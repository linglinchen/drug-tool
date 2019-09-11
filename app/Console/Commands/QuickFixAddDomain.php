<?php

/* populate the 'domain_code' column in atoms table for vet dictionary
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Product;

class QuickFixAddDomain extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:adddomain {productId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command will populate domain_code column in atoms table (mainly for vet dictionary)';

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
        self::_addDomain($productId);
    }

    public static function _addDomain($productId) {
        ini_set('memory_limit', '1280M');
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::legacyBuildLatestIDQuery(null, $q);
                })->where('product_id','=', $productId)->get();
      
        $count = 0;
        foreach($atoms as $atom) {
            $xml = $atom->xml;
            preg_match('/<category.*>(.*)<\/category>/Si', $xml, $matches);
            if ($matches){
                $count++;
                $category = $matches[1];
                $atom->domain_code = $category;
                $atom->save();
            }else{
                echo $atom->id."\n";
            }
        }
        echo 'Total domain added: '.$count."\n";
    }
}