<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Group;

class ImportClearGroups extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cleargroups';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the groups table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        (new Group())->truncate();

        echo "Groups table truncated\n";
    }
}
