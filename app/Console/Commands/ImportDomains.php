<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Domain;


/**
 * Expected field headers for domains.csv:
 *
 * code,title,sort,product_id
 */
class ImportDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:domains';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import domains from data/import/domains.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/domains.csv';
        if(!file_exists($filename)) {
            return;
        }

        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));

        //parse the lines as csv
        foreach($lines as $key => $line) {
            $lines[$key] = str_getcsv($line);
        }

        $headers = array_shift($lines);     //first row is expected to contain the headers

        $sort = 0;
        foreach($lines as $line) {
            $domain = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importDomain($domain);
        }

        echo "Done\n";
    }

    /**
     * Import a domain.
     *
     * @param array $domain The domain as an associative array; currently: code,title,locked,sort,product_id,contributor_id,editor_id
     */
    public function importDomain($domain) {
        $timestamp = (new Domain())->freshTimestampString();

        $domain['created_at'] = $timestamp;
        $domain['updated_at'] = $timestamp;
        DB::table('domains')->insert($domain);
    }
}
