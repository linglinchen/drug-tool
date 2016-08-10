<?php

namespace App\Console;

use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    /**
     * The Artisan commands provided by your application.
     *
     * @var array
     */
    protected $commands = [
        Commands\ImportClearUsers::class,
        Commands\ImportUsers::class,
        Commands\ImportClearGroups::class,
        Commands\ImportGroups::class,
        Commands\ImportClearMolecules::class,
        Commands\ImportMolecules::class,
        Commands\ImportClearAtoms::class,
        Commands\ImportAtoms::class,
        Commands\ImportClearACLStructure::class,
        Commands\ImportACLStructure::class,
        Commands\ImportClearACL::class,
        Commands\ImportACL::class,
        Commands\ImportClearTasks::class,
        Commands\ImportTasks::class,
        Commands\ImportClearStatuses::class,
        Commands\ImportStatuses::class,
    ];

    /**
     * Define the application's command schedule.
     *
     * @param  \Illuminate\Console\Scheduling\Schedule  $schedule
     * @return void
     */
    protected function schedule(Schedule $schedule)
    {
        // $schedule->command('inspire')
        //          ->hourly();
    }
}
