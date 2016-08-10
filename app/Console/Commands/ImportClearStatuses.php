<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Status;

class ImportClearStatuses extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clearstatuses';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the statuses table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        (new Status())->truncate();

        echo "Statuses table truncated\n";
    }
}
