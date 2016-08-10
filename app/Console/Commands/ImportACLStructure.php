<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\AccessControlStructure;


/**
 * Expected field headers for acl_structure.csv:
 *
 * id,parentId,accessKey,title
 */
class ImportACLStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:aclstructure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import molecules from data/import/acl_structure.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/acl_structure.csv';
        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));

        //parse the lines as csv
        foreach($lines as $key => $line) {
            $lines[$key] = str_getcsv($line);
        }

        $headers = array_shift($lines);     //first row is expected to contain the headers

        foreach($lines as $line) {
            $structure = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importStructure($structure);
        }

        echo "Done\n";
    }

    /**
     * Import an ACL structure line.
     *
     * @param array $structure The ACL structure line as an associative array
     */
    public function importStructure($structure) {
        $timestamp = (new AccessControlStructure())->freshTimestampString();


        $nullables = ['parentId'];
        foreach($nullables as $nullable) {
            $structure[$nullable] = $structure[$nullable] === '' ? null : $structure[$nullable];
        }

        $structure['created_at'] = $timestamp;
        $structure['updated_at'] = $timestamp;

        DB::table('access_control_structure')->insert($structure);
    }
}
