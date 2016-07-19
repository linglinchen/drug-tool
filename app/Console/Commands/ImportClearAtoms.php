<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class ImportClearAtoms extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clearatoms';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the atoms table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $atom = new Atom();
        $atom->truncate();

        echo "Atoms table truncated\n";
    }
}
