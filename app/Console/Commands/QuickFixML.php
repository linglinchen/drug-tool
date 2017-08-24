<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Product;
use App\Status;


/**
 * changes ml to mL wherever it thinks this means milliliters. Based on quick fix
*/
class QuickFixML extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'quickfix:ml
						{productId : The ID of product for the quickfix}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command changes ml to mL wherever it thinks this means milliliters in the specified product. Based on quick fix. Does not change status_id';

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
		$this->productId = $productId;
		$doctype = Product::find($productId)->getDoctype();

        self::fixLowercasedML();
    }

    public function fixLowercasedML() {
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->where('product_id','=', $this->productId)->get();
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
        echo 'Product: ' . $this->productId . "\n";
        echo 'Total atoms: ' . count($atoms) . "\n";
        echo 'Total updated atoms: ' . $total_replaced_atoms . "\n";
        echo 'Total text replacements: ' . $total_replaced . "\n";

    }
}
