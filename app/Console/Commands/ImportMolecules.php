<?php

/*
 * Expected field headers for molecules.csv:
 *
 * code,title
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Molecule;

class ImportMolecules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:molecules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import molecules from data/molecules.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/molecules.csv';
        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));

        //parse the lines as csv
        foreach($lines as $key => $line) {
            $lines[$key] = str_getcsv($line);
        }

        $headers = array_shift($lines);     //first row is expected to contain the headers

        foreach($lines as $line) {
            $molecule = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importMolecule($molecule);
        }

        echo "Done\n";
    }

    public function importMolecule($molecule) {
        $timestamp = (new Molecule())->freshTimestampString();

        $molecule['created_at'] = $timestamp;
        $molecule['updated_at'] = $timestamp;

        DB::table('molecules')->insert($molecule);
    }
}
