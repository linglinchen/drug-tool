<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Group;


/**
 * Expected field headers for groups.csv:
 *
 * title
 */
class ImportGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:groups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import molecules from data/import/groups.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/groups.csv';
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
     * Import a group line.
     *
     * @param array $group The group line as an associative array
     */
    public function importGroup($group) {
        $timestamp = (new Group())->freshTimestampString();

        $group['created_at'] = $timestamp;
        $group['updated_at'] = $timestamp;

        DB::table('groups')->insert($group);
    }
}
