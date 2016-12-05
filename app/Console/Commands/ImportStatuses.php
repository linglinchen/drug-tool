<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Status;


/**
 * Expected field headers for statuses.csv:
 *
 * id,title,active,publish,product_id
 */
class ImportStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:statuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import statuses from data/import/statuses.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/statuses.csv';
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
            $status = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importStatuses($status);
        }

        echo "Done\n";
    }

    /**
     * Import a status line.
     *
     * @param array $status The status line as an associative array
     */
    public function importStatuses($status) {
        DB::table('statuses')->insert($status);
    }
}
