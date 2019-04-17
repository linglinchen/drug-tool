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

class QuickFixCloseNursingReviewerAssignments extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'closeNursingReviewerAssignments {userId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Due to Nursing reviewer left, need to close his/her assignments, may need to move to next step if the reviewer is the only reviewer there';

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


        //The reviewer is the only reviewer that have open assignments
        //need to close the assignment and carry to the next step
        $domainIds = [];
        $domains = Domain::where('product_id', '=', 8)
                    ->orderBy('sort', 'ASC')
                    ->get();

        foreach ($domains as $domain){
                $domainIds[$domain['code']]= $domain['id'];
        }

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

            //figure out the next step
            //find out the domain of atom
            $atoms = [];
            $atoms = Atom::where('entity_id', '=', $entityId2)
                        ->get();
            $atom = $atoms->last();
            preg_match_all('/<category[^>]*>(.*)<\/category>/Si', $atom->xml, $matches);
            $uniqueDomains = array_unique($matches[1]);
            $ebUserIds = self::_getUserIds($uniqueDomains, $domainIds, 802);//802 is editorial board group id
            if (!empty($ebUserIds)){
                foreach ($ebUserIds as $ebUserId){
                    self::_makeAssignment($entityId2, $ebUserId, 804); //804 : editorial board review
                }
                echo 'assigned to Editorial Board ' .$entityId2. ' ' .$atom->alpha_title. "\n";
            }
            else{ //assign to author
                self::_makeAssignment($entityId2, 820, 805); //820: author userID  ; 805 : assign to author
                echo 'assigned to author ' .$entityId2. ' ' .$atom->alpha_title. "\n";
            }
        
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
        // $existing_assignments =
        //     Assignment::where('atom_entity_id', '=', $entityId)
        //         ->where('task_id', '=', $taskId)
        //         ->where('user_id', '=', $userId)
        //         ->get()
        //         ->last();

        //check if the atom has exsiting assignments, some new terms have been generated
        $existing_assignments =
            Assignment::where('atom_entity_id', '=', $entityId)
                ->where('task_id', '!=', $taskId)
                ->get()
                ->last();

        if (is_null($existing_assignments)){
            Assignment::query()->insert($assignment);
        }
    }

    protected static function _getUserIds($uniqueDomains, $domainIds, $groupId){
        $userIds = [];
        foreach ($uniqueDomains as $uniqueDomain){
            if ($uniqueDomain !== ' ' && $domainIds[$uniqueDomain]){
                $domainId = $domainIds[$uniqueDomain];
                $userIdsByDomain = UserDomain::getUserIds($domainId, $groupId);
                foreach ($userIdsByDomain as $userIdByDomain){
                    $userIds[] = $userIdByDomain;
                }
            }
        }
        sort($userIds);
        $userIds = array_unique($userIds);
        return $userIds;
    }
}