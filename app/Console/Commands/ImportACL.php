<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\AccessControl;


/**
 * Expected field headers for acl.csv:
 *
 * id,userId,groupId,accessControlStructureId,permitted
 */
class ImportACL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:acl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import ACL from data/import/acl.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/acl.csv';
        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));

        //parse the lines as csv
        foreach($lines as $key => $line) {
            $lines[$key] = str_getcsv($line);
        }

        $headers = array_shift($lines);     //first row is expected to contain the headers

        foreach($lines as $line) {
            $accessControl = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importAccessControl($accessControl);
        }

        echo "Done\n";
    }

    /**
     * Import an ACL line.
     *
     * @param array $accessControl The ACL line as an associative array
     */
    public function importAccessControl($accessControl) {
        $timestamp = (new AccessControl())->freshTimestampString();

        $nullables = ['userId', 'groupId'];
        foreach($nullables as $nullable) {
            $accessControl[$nullable] = $accessControl[$nullable] === '' ? null : $accessControl[$nullable];
        }

        $accessControl['created_at'] = $timestamp;
        $accessControl['updated_at'] = $timestamp;

        DB::table('access_control')->insert($accessControl);
    }
}
