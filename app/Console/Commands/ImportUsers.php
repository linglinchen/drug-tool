<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\User;


/**
 * Expected field headers for users.csv:
 *
 * username,email,firstname,lastname,groupId,password
 */
class ImportUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:users';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import users from data/import/users.csv';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/users.csv';
        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));

        //parse the lines as csv
        foreach($lines as $key => $line) {
            $lines[$key] = str_getcsv($line);
        }

        $headers = array_shift($lines);     //first row is expected to contain the headers

        foreach($lines as $line) {
            $user = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            $this->importUser($user);
        }

        echo "Done\n";
    }

    /**
     * Import a user. The password field will be hashed automatically.
     *
     * @param array $user The user as an associative array
     */
    public function importUser($user) {
        $timestamp = (new User())->freshTimestampString();

        //set up a few fields that can't be imported directly from the csv
        $user['password'] = isset($user['password']) ? Hash::make($user['password']) : '';
        $user['created_at'] = $timestamp;
        $user['updated_at'] = $timestamp;

        DB::table('users')->insert($user);
    }
}
