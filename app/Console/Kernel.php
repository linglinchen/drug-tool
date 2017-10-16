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
        Commands\ImportDomains::class,
        Commands\ImportAtoms::class,
        Commands\ImportACLStructure::class,
        Commands\ImportACL::class,
        Commands\ImportTasks::class,
        Commands\ImportStatuses::class,
        Commands\ImportUserProducts::class,
        Commands\ImportBoilerplates::class,
        Commands\CreateAssignments::class,
        Commands\QuickFix::class,
        Commands\QuickFixML::class,
        Commands\QuickFixXmlns::class,
        Commands\QuickFixRX::class,
        Commands\QuickFixOrder::class,
        Commands\QuickFixIV::class,
        Commands\QuickFixLowercase::class,
        Commands\QuickFixColon::class,
        Commands\QuickFixAddDomain::class,
        Commands\QuickFixPara::class,
        Commands\QuickFixXrefs::class,
        Commands\QuickFixStatus::class,
        Commands\ExtractRarelyUsed::class,
        Commands\UpdateDomains::class,
        Commands\AssignReviewerTasks::class,
        Commands\CreateAtomDomainsList::class,
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
