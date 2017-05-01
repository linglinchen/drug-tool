<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\Atom;
use App\Molecule;
use App\Product;
use App\Status;

/**
 * Imports atoms from XML file(s) in the data/import/atoms directory. Automatically applies tallman tags to text.
 * When editing tallman.txt, be sure that you capitalize ONLY the characters that you want tagged as tallman.
 * To avoid headaches, run this after creating the molecules.
 *
 * Reimports probably work, but are untested.
 */
class ImportAtoms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:atoms {productId} {statusId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import atoms from XML file(s) in the data/import/atoms directory, and place them into the specified product. Do not run without first importing or adding molecules.';

    /**
     * The contents of the molecules table
     *
     * @var array
     */
    protected $moleculeLookups;

    /**
     * A translation table for tallman drug names
     *
     * @var string[]
     */
    protected $tallman = [];

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

        $statusId = (int)$this->argument('statusId');
        $statusCount = Status::allForProduct($productId)
                ->where('id', '=', $statusId)
                ->count();
        if(!$statusId || !$statusCount) {
            throw new \Exception('Invalid status ID.');
        }
        $this->statusId = $statusId;

        $this->moleculeLookups = Molecule::getLookups($productId);

        $dataPath = base_path() . '/data/import/atoms/';
        $files = scandir($dataPath);
        $files = array_slice($files, 2);
        foreach($files as $file) {
            if(!preg_match('/\.xml$/i', $file)) {
                continue;       //skip non-xml file
            }

            echo 'Loading ', $file, "\n";

            $xml = file_get_contents($dataPath . $file);
            $xml = $this->_addTallman($xml);

            $chapters = $doctype->extractAtomXML($xml);
            if($chapters) {
                foreach($chapters as $moleculeCode => $atoms) {
                    $atomCount = $this->_importAtoms($atoms, $moleculeCode);
                    echo "\t", $moleculeCode, ' - ', $atomCount, ' atom' . ($atomCount != 1 ? 's' : '') . "\n";
                }
            }
            else {
                $atomCount = $this->_importAtoms($atoms);
                echo "\t<no molecule detected> ", $atomCount, "\n";
            }

            echo "\n";
        }

        echo "Done\n";
    }

    /**
     * Import an array of atom XML strings. Usually a whole letter node.
     *
     * @param string[] $atoms The XML strings to import
     * @param string|null $moleculeCode (optional) The code of the molecule that these atom belong to
     *
     * @return int The number of atoms imported
     */
    public function _importAtoms($atoms, $moleculeCode = null) {
        $atom = new Atom();
        $doctype = Product::find($this->productId)->getDoctype();
        $sort = 0;
        foreach($atoms as $atomString) {
            $category = '';
            preg_match('/<category[^>]*>(.*)<\/category>/Si', $atomString, $matches);
            if ($matches){
                $category = $matches[1];
            }
            $title = $doctype->detectTitle($atomString);
            $alphaTitle = Atom::makeAlphaTitle($title);
            $timestamp = $atom->freshTimestampString();
            $entityId = $doctype->detectAtomIDFromXML($atomString) ?: Atom::makeUID();
            $atomString = $doctype->assignXMLIds($atomString, $entityId);
            $sort++;

            $atomData = [
                'entity_id' => $entityId,
                'title' => $title,
                'alpha_title' => $alphaTitle,
                'molecule_code' => $moleculeCode,
                'xml' => $atomString,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
                'status_id' => $this->statusId,
                'product_id' => $this->productId,
                'sort' => $sort,
                'domain_code' => $category,
            ];

            DB::table('atoms')->insert($atomData);
        }

        return sizeof($atoms);
    }

    /**
     * Add tallman tags to XML.
     *
     * @param string $xml The XML string to modify
     *
     * @return string The modified XML string
     */
    protected function _addTallman($xml) {
        $this->_loadTallman();

        foreach($this->tallman as $find => $replacement) {
            $xml = preg_replace($find, $replacement, $xml);
        }

        return $xml;
    }

    /**
     * Loads the tallman data if needed, and computes searches / replacements.
     */
    protected function _loadTallman() {
        if(!$this->tallman) {
            $dataPath = base_path() . '/data/tallman.txt';
            $tallman = file_get_contents($dataPath);
            $tallman = preg_split('/\v+/S', trim($tallman));
            foreach($tallman as $name) {
                $name = trim($name);

                if(!$name) {
                    continue;       //skip blank lines
                }

                //build inner portion of the search regex
                $find = preg_replace('/[A-Z]+/S', '($0)', $name);
                $find = preg_replace('/[a-z]+/S', '($0)', $find);

                //build the replacement
                $i = 0;
                $parts = explode(')(', $find);
                $replacement = '';
                foreach($parts as $part) {
                    if(strtolower($part) == $part) {
                        $replacement .= '$' . ++$i;     //lowercase
                    }
                    else {
                        $replacement .= '<emphasis style="tallman">$' . ++$i . '</emphasis>';     //uppercase
                    }
                }

                //finish building the search regex
                $find = '#\b' . $find . '\b#Si';

                $this->tallman[$find] = $replacement;
            }
        }
    }
}
