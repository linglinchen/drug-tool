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
        Commands\ImportClear::class,
        Commands\ImportUsers::class,
        Commands\ImportGroups::class,
        Commands\ImportMolecules::class,
        Commands\ImportAtoms::class,
        Commands\ImportACLStructure::class,
        Commands\ImportACL::class,
        Commands\ImportTasks::class,
        Commands\ImportStatuses::class,
        Commands\CreateAssignments::class,
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
