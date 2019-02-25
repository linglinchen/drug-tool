<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Domain;
use App\UserDomain;
use App\Assignment;
ini_set('memory_limit', '1280M');

class AssignReviewerTasksNursing extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignreviewertasksnursing';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make appropriate assignments for reviewers across all words based on domains for Nursing';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
		$atoms = [];
		$domainIds = [];
		$timestamp = (new Atom())->freshTimestampString();

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
			preg_match_all('/<category[^>]*>(.*)<\/category>/Si', $atom->xml, $matches);
            $uniqueDomains = array_unique($matches[1]);
            $userIds = self::_getUserIds($uniqueDomains, $domainIds, 803);//803 is reviewer group id
            if (!empty($userIds)){
                foreach ($userIds as $userId){
                    self::_makeAssignment($entityId, $userId, 802); //802 : reviewer performs initial review
                }
                echo 'assigned to reviewer ' .$entityId. ' ' .$atom->alpha_title. "\n";
            }
            else{ //assign to Sarah
                self::_makeAssignment($entityId, 13, 801); //801: Editorial initial review   13:Sara userId
                echo 'assigned to reviewer ' .$entityId. ' ' .$atom->alpha_title. "\n";
            }
            // else{ // if no reviewer, check if it can go to editorial board
            //     $userIds = self::_getUserIds($uniqueDomains, $domainIds, 802);//802 is editorial board group id
            //     if (!empty($userIds)){ print_r($entityId);
            //         foreach ($userIds as $userId){
            //             self::_makeAssignment($entityId, $userId, 804); //804 : editorial board review
            //             echo 'assigned to editorial board ' .$entityId. ' ' .$atom->alpha_title. "\n";
            //         }
            //     }else{ //no reviewer, no editorial board
            //         self::_makeAssignment($entityId, 820, 805); //820: author userID  ; 805 : assign to author
            //         echo 'assigned to author ' .$entityId. ' ' .$atom->alpha_title. "\n";
            //     }
            // }
        }
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