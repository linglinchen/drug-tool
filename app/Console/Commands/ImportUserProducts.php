<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\UserProduct;


/**
 * Expected field headers for groups.csv:
 *
 * title,product_id
 */
class ImportUserProducts extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:userproducts';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import groups from data/import/user_products.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/user_products.csv';
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
            $group = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importGroup($group);
        }

        echo "Done\n";
    }

    /**
     * Import a user_product line.
     *
     * @param array $userProduct The user_product line as an associative array
     */
    public function importGroup($userProduct) {
        $timestamp = (new UserProduct())->freshTimestampString();

        $userProduct['created_at'] = $timestamp;
        $userProduct['updated_at'] = $timestamp;

        DB::table('user_products')->insert($userProduct);
    }
}
