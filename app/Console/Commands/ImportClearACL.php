<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\AccessControl;

class ImportClearACL extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clearacl';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the access_controls table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        (new AccessControl())->truncate();

        echo "Access_controls table truncated\n";
    }
}
