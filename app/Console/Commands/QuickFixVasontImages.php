<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Product;
use App\Status;


/**
 * Meant to work with Vet, product id 3. Finds image src="vasontId" references and replaces the whole source reference with the appropriate file stub name for files stored on S3. Based on quick fix
*/
class QuickFixVasontImages extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
	protected $signature = 'quickfix:vasontimages
						{productId : The ID of product for the quickfix}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command so far is meant to work with Vet, product id 3. It does a find of image src="vasontId" references and replaces the whole source reference with the appropriate file stub name for files stored on S3';

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
//		$doctype = Product::find($productId)->getDoctype();

        self::swapAWSforVasont();
    }

    public function swapAWSforVasont() {
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->where('product_id','=', $this->productId)->get();
        $total_replaced = 0;
        $total_replaced_atoms = 0;
        //include file with giant search replace array to match up vasont ids and s3 files
        include_once "veterinary_dictionary_image_renamer.php";

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
