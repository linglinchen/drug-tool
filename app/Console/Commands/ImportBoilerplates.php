<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\Atom;
use App\Product;
use App\Status;

/**
 * Imports boilerplates from XML file(s) in the data/import/boilerplates directory.
 * To avoid headaches, run this after creating the products.
 */
class ImportBoilerplates extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:boilerplates {productId} {boilerTitles*}';

	protected $signature = 'import:boilerplates
                        {productId : The ID of product for the boilerplate}
                        {boilerTitles* : An array of titles to use for each boilerplate XML file, in file alpha order}';


    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import boilerplates from XML file(s) in the data/import/boilerplates directory, and place them into the specified product using an array for boilerplate titles. Do not run without first importing or adding products.';

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

        $boilerTitle = (int)$this->argument('boilerTitle');
        if(!$boilerTitle) {
            throw new \Exception('Invalid boilerplate title.');
        }
        $this->boilerTitle = $boilerTitle;

        $this->moleculeLookups = Molecule::getLookups($productId);

        $dataPath = base_path() . '/data/import/boilerplates/';
        $files = scandir($dataPath);
        $files = array_slice($files, 2);
        foreach($files as $file) {
            if(!preg_match('/\.xml$/i', $file)) {
                continue;       //skip non-xml file
            }

            echo 'Loading ', $file, "\n";

            $xml = file_get_contents($dataPath . $file);

            $chapters = $doctype->extractAtomXML($xml);
            if($chapters) {
                foreach($chapters as $moleculeCode => $atoms) {
                    $atomCount = $this->_importAtoms($atoms, $moleculeCode);
                    echo "\t", $moleculeCode, ' - ', $atomCount, ' atom' . ($atomCount != 1 ? 's' : '') . "\n";
                }
            }
            else {
                $atomCount = $this->_importBoilerplates($boilerplates);
                echo "\t<no molecule detected> ", $atomCount, "\n";
            }

            echo "\n";
        }

        echo "Done\n";
    }

    /**
     * Import an array of boilerplate XML strings. Usually a whole letter node.
     *
     * @param string[] $atoms The XML strings to import
     * @param string|null $moleculeCode (optional) The code of the molecule that these atom belong to
     *
     * @return int The number of atoms imported
     */
    public function _importBoilerplates($boilerplates, $moleculeCode = null) {
        $boilerplate = new Boilerplate();
        $doctype = Product::find($this->productId)->getDoctype();
        $sort = 0;
        foreach($boilerplates as $boilerplateString) {
            $category = '';
            preg_match('/<category[^>]*>(.*)<\/category>/Si', $atomString, $matches);
            if ($matches){
                $category = $matches[1];
            }
            $title = $doctype->detectTitle($atomString);
            $alphaTitle = Atom::makeAlphaTitle($title);
            $boilerplateString = $doctype->assignXMLIds($boilerplateString, $entityId);
            $sort++;

            $boilerplateData = [
                'title' => $title,
                'xml' => $boilerplateString,
                'product_id' => $this->productId,
            ];

            DB::table('boilerplates')->insert($boilerplateData);
        }

        return sizeof($boilerplates);
    }

}
