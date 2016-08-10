<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Task;

class ImportClearTasks extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:cleartasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the tasks table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        (new Task())->truncate();

        echo "Tasks table truncated\n";
    }
}
