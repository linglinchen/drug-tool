<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\Atom;
use App\Molecule;

class ImportAtoms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:atoms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import atoms from XML file(s) in the data/atoms directory. Do not run without importing or adding molecules first.';

    protected $moleculeLookups;

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $this->moleculeLookups = Molecule::getLookups();

        $dataPath = base_path() . '/data/atoms/';
        $files = scandir($dataPath);
        $files = array_slice($files, 2);
        foreach($files as $file) {
            if(!preg_match('/\.xml$/i', $file)) {
                continue;       //skip non-xml file
            }

            echo 'Loading ', $file, "\n";

            $xml = file_get_contents($dataPath . $file);
            preg_match_all('/<alpha\b.*?<\/alpha>/SUis', $xml, $alphas);
            $alphas = $alphas ? $alphas[0] : [];

            if($alphas) {
                foreach($alphas as $alpha) {
                    preg_match('/<alpha letter="(\w*)"/SUis', $alpha, $moleculeCode);
                    $moleculeCode = $moleculeCode ? $moleculeCode[1] : null;
                    self::importXMLChunk($alpha, $moleculeCode);
                }
            }
            else {
                self::importXMLChunk($xml);
            }
        }

        echo "Done\n";
    }

    public function importXMLChunk($xml, $moleculeCode = null) {
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

        foreach($atomTypes as $atomType) {
            $elementName = $atomType['elementName'];
            $titleElement = $atomType['titleElement'];

            extract(self::extractAtoms($xml, $elementName));

            foreach($atoms as $atomString) {
                preg_match('/<' . $titleElement . '>(.*)<\/' . $titleElement . '>/SUis', $atomString, $match);
                $title = isset($match[1]) ? trim($match[1]) : 'Missing title';
                $alphaTitle = strip_tags($title);
                $timestamp = $atom->freshTimestampString();

                DB::table('atoms')->insert([
                    'entityId' => Atom::makeUID(),
                    'title' => $title,
                    'alphaTitle' => $alphaTitle,
                    'moleculeCode' => $moleculeCode,
                    'xml' => Atom::assignXMLIds(trim($atomString)),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ]);
            }
        }
    }

    /*
     * This method gently extracts only the top-level atoms' XML without altering it in any way.
     */
    public static function extractAtoms($xml, $tagName) {
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
