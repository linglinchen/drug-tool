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
        Commands\ImportUserDomains::class,
        Commands\ImportContentArea::class,
        Commands\ImportHealthCodes::class,
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
        Commands\QuickFixVasontImages::class,
        Commands\QuickFixXrefs::class,
        Commands\QuickFixStatus::class,
        Commands\QuickFixRemoveLineSeparator::class,
        Commands\QuickFixEICtoPHARM::class,
        Commands\QuickFixVetXref::class,
        Commands\QuickFixAssignLaser::class,
        Commands\QuickFixAssignToMargaret::class,
        Commands\QuickFixRecoverDentalPharm::class,
        Commands\QuickFixDentalMargaret::class,
        Commands\QuickFixRemoveQuesReference::class,
        Commands\QuickFixImageAvailability::class,
        Commands\QuickFixContentArea::class,
        Commands\QuickFixPos::class,
        Commands\QuickFixCloseNursingReviewerAssignments::class,
        Commands\ExtractRarelyUsed::class,
        Commands\UpdateDomains::class,
        Commands\AssignReviewerTasks::class,
        Commands\AssignReviewerTasksNursing::class,
        Commands\CreateAtomDomainsList::class,
        Commands\ListVetPotentialDeactivated::class,
        Commands\GetModifiedTermsVet::class,
        Commands\GetModifiedHeadwVet::class,
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
