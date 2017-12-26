<?php

/* search for assignments that belong to term with category of 'PHARM', and is assigned to EIC (Margaret), with task as 'Author revision', with task_end=NULL
 * change those assignments to have user_id as PHARM's two contributors (533, 534)
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Assignment;
use App\Product;
use App\Status;
use Carbon\Carbon;

class QuickFixEICtoPHARM extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:EICtoPHARM';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command searches for assignments that belong to term with category of PHARM, and is assigned to EIC (Margaret), with task as \'Author revision\', with task_end=NULL, then change those assignments\' user_id to be 533 and 534.  e.g. quickfix:EICtoPHARM';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $sql = "select ass.id, ass.atom_entity_id, ass.user_id, ass.task_id, ass.task_end, ass.created_at, ass.updated_at, ass.created_by from assignments ass
                join (
                        select MAX(id) as id, entity_id, domain_code from atoms
                        where product_id=5 and domain_code = 'PHARM'
                        group by entity_id, domain_code
                      ) atomtable
                on atomtable.entity_id = ass.atom_entity_id
                where user_id=241 and task_id=556 and task_end is NULL";
        $assignments = DB::select($sql);
        $assignmentsArray = json_decode(json_encode($assignments), true);  //convert object to array
        $totalDetectedAssignments = sizeof($assignmentsArray);
        $changedAssignments = 0;
        $timestamp = Carbon::now()->toDateTimeString();
        foreach($assignmentsArray as $assignment) {
            $assignmentModel = Assignment::find($assignment['id']);
            self::make_newAssignment($assignment, 533, $timestamp);
            self::make_newAssignment($assignment, 534, $timestamp);
            $assignmentModel->task_end = $timestamp;
            $assignmentModel->save();
        }

        /* output messages */
        echo 'affected Assignments: '.$totalDetectedAssignments."\n";
    }

    public static function make_newAssignment($oldAssignment, $userId, $timestamp) {
         $assignment = new Assignment();
         $assignment->atom_entity_id = $oldAssignment['atom_entity_id'];
         $assignment->user_id = $userId;
         $assignment->task_id = $oldAssignment['task_id'];
         $assignment->task_end = NULL;
         $assignment->created_at = $timestamp;
         $assignment->updated_at = $timestamp;
         $assignment->created_by = NULL;
         $assignment->save();
    }
}