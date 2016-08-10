<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\AccessControlStructure;

class ImportClearACLStructure extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clearaclstructure';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the access_control_structures table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $atom = new AccessControlStructure();
        $atom->truncate();

        echo "Access_control_structures table truncated\n";
    }
}
