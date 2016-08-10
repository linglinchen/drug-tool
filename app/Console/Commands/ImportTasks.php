<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Task;


/**
 * Expected field headers for tasks.csv:
 *
 * title
 */
class ImportTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:tasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import tasks from data/import/tasks.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/tasks.csv';
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
            $task = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importTask($task);
        }

        echo "Done\n";
    }

    /**
     * Import a task line.
     *
     * @param array $task The task line as an associative array
     */
    public function importTask($task) {
        DB::table('tasks')->insert($task);
    }
}
