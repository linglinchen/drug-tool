<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class ImportClear extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clear {table}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the specified table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $table = $this->argument('table');
        \DB::table($table)->truncate();

        echo $table . " table truncated\n";
    }
}
