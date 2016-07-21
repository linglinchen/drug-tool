<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Molecule;

class ImportClearMolecules extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:clearmolecules';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Truncate the molecules table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle()
    {
        $molecule = new Molecule();
        $molecule->truncate();

        echo "Molecules table truncated\n";
    }
}
