<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\Atom;

class ImportXml extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:xml';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import atoms from XML file(s) in the data directory';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $atom = new Atom();

        $dataPath = base_path() . '/data/';
        $files = scandir($dataPath);
        $files = array_slice($files, 2);
        foreach($files as $file) {
            echo 'Loading ', $file, "\n";

            $xml = file_get_contents($dataPath . $file);
            preg_match_all('/<monograph.*?<\/monograph>/Sis', $xml, $monographs);
            $monographs = $monographs[0];
            foreach($monographs as $monograph) {
                preg_match('/<mono_name>(.*?)<\/mono_name>/i', $monograph, $match);
                $title = trim($match[1]);
                $alphaTitle = strip_tags($title);
                $timestamp = $atom->freshTimestampString();

                DB::table('atoms')->insert([
                    'entityId' => Atom::makeUID(),
                    'title' => $title,
                    'alphaTitle' => $alphaTitle,
                    'xml' => trim($monograph),
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ]);
            }
        }

        echo "Done\n";
    }
}
