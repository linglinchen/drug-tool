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
            throw new Exception('Invalid product ID.');
        }
        $this->productId = $productId;

        $statusId = (int)$this->argument('statusId');
        $statusCount = Status::allForProduct($productId)
                ->where('id', '=', $statusId)
                ->count();
        if(!$statusId || $statusCount) {
            throw new Exception('Invalid status ID.');
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

            $alphas = $this->_extractAlphas($xml);
            if($alphas) {
                foreach($alphas as $alpha) {
                    preg_match('/<alpha\b[^>]letter="([^"]*)"/Sis', $alpha, $moleculeCode);
                    $moleculeCode = $moleculeCode ? trim($moleculeCode[1]) : null;
                    $atomCount = self::_importXMLChunk($alpha, $moleculeCode);

                    echo "\t", $moleculeCode, ' - ', $atomCount, ' atom' . ($atomCount != 1 ? 's' : '') . "\n";
                }
            }
            else {        //we don't know which letter(s) these atoms belong to
                $atomCount = self::_importXMLChunk($xml);

                echo "\t<no molecule detected> ", $atomCount, "\n";
            }

            echo "\n";
        }

        echo "Done\n";
    }

    /**
     * Break XML into alpha sections, and remove everything else.
     *
     * @param string $xml The XML string to extract from
     *
     * @return string[] The extracted alpha sections
     */
    protected function _extractAlphas($xml) {
        $alphas = explode('</alpha>', $xml);
        array_pop($alphas);

        foreach($alphas as $key => &$alpha) {
            $alpha = explode('<alpha', $alpha);
            $alpha = $alpha[1];

            $alphas[$key] = '<alpha' . $alpha . '</alpha>';
        }

        return $alphas;
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

    /**
     * Import an XML string. Usually a whole letter node.
     *
     * @param string $xml The XML string to import
     * @param string|null $moleculeCode (optional) The code of the molecule that this atom belongs to
     *
     * @return int The number of atoms imported
     */
    protected function _importXMLChunk($xml, $moleculeCode = null) {
        $atom = new Atom();

        //atom types must be in order of priority, or your data will be mangled
        $atomTypes = [
            [
                'elementName' => 'group',
                'titleElement' => 'group_title'
            ],
            [
                'elementName' => 'monograph',
                'titleElement' => 'mono_name'
            ]
        ];

        $atomCount = 0;
        foreach($atomTypes as $atomType) {
            $elementName = $atomType['elementName'];
            $titleElement = $atomType['titleElement'];

            extract(self::_extractAtoms($xml, $elementName));

            $atomCount += sizeof($atoms);
            foreach($atoms as $atomString) {
                preg_match('/<' . $titleElement . '>(.*)<\/' . $titleElement . '>/SUis', $atomString, $match);
                $title = isset($match[1]) ? trim($match[1]) : 'Missing title';
                $alphaTitle = strip_tags($title);
                $timestamp = $atom->freshTimestampString();
                
                $entityId = Atom::detectAtomIDFromXML($atomString);
                $entityId === null ? Atom::makeUID() : $entityId;
                
                $atomString = Atom::assignXMLIds(trim($atomString));

                $atomData = [
                    'entity_id' => $entityId,
                    'title' => $title,
                    'alpha_title' => $alphaTitle,
                    'molecule_code' => $moleculeCode,
                    'xml' => $atomString,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                    'status_id' => $this->statusId,
                    'product_id' => $this->productId
                ];

                DB::table('atoms')->insert($atomData);
            }
        }

        return $atomCount;
    }

    /*
     * This method gently extracts only the top-level atoms' XML without altering it in any way.
     *
     * @param string $xml The XML string to import
     * @param string $tagName The type of tag we would like to extract
     *
     * @return array The extracted atoms and the xml string with those atoms removed
     */
    protected static function _extractAtoms($xml, $tagName) {
        //find the top-level atoms' bookends
        $level = 0;
        $bookends = [];
        preg_match_all('/(<' . $tagName . '[ >]|<\/' . $tagName . '>)/Si', $xml, $matches, PREG_OFFSET_CAPTURE);
        foreach($matches[0] as $tag) {
            $isOpenTag = strpos($tag[0], '/') === false;
            $level += $isOpenTag ? 1 : -1;

            //record bookends as needed
            if($isOpenTag && $level == 1) {
                $bookend = [$tag[1]];
            }
            elseif(!$isOpenTag && $level == 0) {
                $bookend[] = $tag[1] + strlen($tag[0]);
                $bookends[] = $bookend;
            }
        }

        //now that we have the bookends, we can extract the atoms
        $atoms = [];
        foreach($bookends as $bookend) {
            $atoms[] = substr($xml, $bookend[0], $bookend[1] - $bookend[0]);
        }

        //remove the atoms that we just extracted
        $bookends = array_reverse($bookends);
        foreach($bookends as $bookend) {
            $xml = substr_replace($xml, '', $bookend[0], $bookend[1] - $bookend[0]);
        }

        return [
            'xml' => $xml,
            'atoms' => $atoms
        ];
    }
}
