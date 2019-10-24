<?php

namespace App\Console\Commands;
use Illuminate\Support\Facades\DB;
use Illuminate\Console\Command;
use App\User;
use App\Atom;
use App\Domain;
use App\UserDomain;
use App\Assignment;
ini_set('memory_limit', '1280M');

class QuickFixCloseNursingDummyAssi extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closeNursingDummyAssi {userId}'; //php artisan quickfix:closeNursingDummyAssi 814

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Due to never finding editorial board (nursing@elsevier.com, user_id=814)  need to close his/her assignments, may need to move to next step if the 814 is the only editorial board there';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $userId = (int)$this->argument('userId');
        if(!$userId || !User::find($userId)) {
            throw new \Exception('Invalid user ID.');
        }

        $timestamp = (new Atom())->freshTimestampString();

        //There are other reviewer(s) that have open assignments with the same task
        //only need to close the assignment
        $sql1 = "select * from 
            (select atom_entity_id from assignments where atom_entity_id in 
                (select atom_entity_id from assignments where user_id = ". $userId ." and task_end is null) 
                and task_end is null order by atom_entity_id
            ) as t 
            group by atom_entity_id having count(*) > 1
        ";

        $assignments1 = DB::select($sql1);
        $assignment1Array = json_decode(json_encode($assignments1), true);
        foreach ($assignment1Array as $assignment1){
            $entityId1 = $assignment1['atom_entity_id'];
            $assign1Model = Assignment::where('atom_entity_id', '=', $entityId1)
                            ->where('user_id', '=', $userId)
                            ->where('task_end', '=', null)
                            ->first();
            $assign1Model->task_end = $timestamp;
            $assign1Model->save();
        }


        //The EB is the only EB that have open assignments
        //need to close the assignment and carry to the next step

       $sql2 = "select * from 
            (select atom_entity_id from assignments where atom_entity_id in 
                (select atom_entity_id from assignments where user_id = ". $userId ." and task_end is null) 
                and task_end is null order by atom_entity_id
            ) as t 
            group by atom_entity_id having count(*) = 1
        ";

        $assignments2 = DB::select($sql2);
        $assignment2Array = json_decode(json_encode($assignments2), true);
        foreach ($assignment2Array as $assignment2){
            $entityId2 = $assignment2['atom_entity_id'];
            $assign2Model = Assignment::where('atom_entity_id', '=', $entityId2)
                            ->where('user_id', '=', $userId)
                            ->where('task_end', '=', null)
                            ->first();
            $assign2Model->task_end = $timestamp;
            $assign2Model->save();  //close the assignment

            self::_makeAssignment($entityId2, 820, 820);
        
        }
    }

   
    protected static function _makeAssignment($entityId, $userId, $taskId){
        $assignment = [
            'atom_entity_id' => $entityId,
            'user_id' => $userId,
            'task_id' => $taskId,   //reviewer performs initial review
            'task_end' => null
        ];

        //check if the atom has been assigned
        $existing_assignments =
            Assignment::where('atom_entity_id', '=', $entityId)
                ->where('task_id', '=', $taskId)
                ->where('user_id', '=', $userId)
                ->get()
                ->last();
       

        if (is_null($existing_assignments)){
            Assignment::query()->insert($assignment);
        }
    }
}