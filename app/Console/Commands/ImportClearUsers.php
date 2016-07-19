<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\User;

class ImportClearUsers extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clearusers';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the users table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $user = new User();
        $user->truncate();

        echo "Users table truncated\n";
    }
}
