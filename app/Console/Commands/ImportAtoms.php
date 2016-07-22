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
            $atomRegex = '/<' . $elementName . '\b.*<\/' . $elementName . '>/SUis';

            preg_match_all($atomRegex, $xml, $atoms);
            $xml = preg_replace($atomRegex, '', $xml);      //clean up before the next round
            $atoms = $atoms[0];
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
                    'xml' => trim($atomString),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ]);
            }
        }
    }
}
