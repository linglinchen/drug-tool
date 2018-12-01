<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\UserDomain;


/**
 * Expected field headers for userdomains.csv:
 *
 * user_id,domain_id,group_id
 */
class ImportUserDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:userdomains';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import user_id and domain_id from data/import/user_domains.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/user_domains.csv';
        if(!file_exists($filename)) {
            return;
        }

        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));

        //parse the lines as csv
        foreach($lines as $key => $line) {
            $lines[$key] = str_getcsv($line);
        }

        $headers = array_shift($lines);     //first row is expected to contain the headers

        foreach($lines as $line) {
            $userDomain = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            DB::table('users_domains')->insert($userDomain);
        }

        echo "Done\n";
    }
}
