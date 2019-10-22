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

class QuickFixFinishMoveNursingReviewerAssi extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'finishNursingReviewerAssignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'For some reason, Category General Nursing and Procedures reviewer assignments were closed, but not moved to next step. This script is to move to next step for those terms';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $timestamp = (new Atom())->freshTimestampString();
        $domainIds = [];
        $domains = Domain::where('product_id', '=', 8)
                    ->orderBy('sort', 'ASC')
                    ->get();

        foreach ($domains as $domain){
                $domainIds[$domain['code']]= $domain['id'];
        }

        $entities = self::_organizeAtoms(self::_getAtomList());
        foreach($entities as $entityId => $versionIds) {
            //find out the domain of atom
            $atoms = Atom::where('entity_id', '=', $entityId)
                        ->get();
            $atom = $atoms->last();
        

        //find all the entity_id that has task 802 (reviewers review)
        $sql1 = "select * from assignments where atom_entity_id = '". $atom->entity_id ."' order by id" ;
        $assignments1 = DB::select($sql1);
        $assignment1Array = json_decode(json_encode($assignments1), true);
        $flag = 0;

        foreach ($assignment1Array as $assignment1){
            $taskArry[] = $assignment1['task_id'];
            if ($assignment1['task_end'] == NULL){ //if there's open assignments
                $flag = 1;
            }
        }

        $lastAssignment = end($assignment1Array);
        //if there's no open assignments and the last assignment task_id=802 (reviewer review)
        if ($flag == 0 && $lastAssignment['task_id'] == 802){
            //need to create next step
            preg_match_all('/<category[^>]*>(.*)<\/category>/Si', $atom->xml, $matches);
            $uniqueDomains = array_unique($matches[1]);
            $ebUserIds = self::_getUserIds($uniqueDomains, $domainIds, 802);//802 is editorial board group id
            if (!empty($ebUserIds)){
                foreach ($ebUserIds as $ebUserId){
                    self::_makeAssignment($entityId, $ebUserId, 804); //804 : editorial board review
                }
                echo 'assigned to Editorial Board ' .$entityId. ' ' .$atom->alpha_title. "\n";
            }
            else{ //assign to author
                self::_makeAssignment($entityId, 820, 805); //820: author userID  ; 805 : assign to author
                echo 'assigned to author ' .$entityId. ' ' .$atom->alpha_title. "\n";
            }

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
        $existing_assignments =
            Assignment::where('atom_entity_id', '=', $entityId)
                ->where('task_id', '=', $taskId)
                ->where('user_id', '=', $userId)
                ->get()
                ->last();

        //check if the atom has exsiting assignments, some new terms have been generated
        // $existing_assignments =
        //     Assignment::where('atom_entity_id', '=', $entityId)
        //         ->where('task_id', '!=', $taskId)
        //         ->get()
        //         ->last();

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

    protected static function _getAtomList() {
        return Atom::select(['id', 'entity_id'])->where('product_id', '=', 8)->orderBy('id')->get();
    }

    protected static function _organizeAtoms($list) {
        $output = [];
        foreach($list as $row) {
            if(!isset($output[$row['entity_id']])) {
                $output[$row['entity_id']] = [];
            }

            $output[$row['entity_id']][] = $row['id'];
        }
        return $output;
    }
}