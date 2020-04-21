<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Domain;
use App\UserDomain;
use App\Assignment;
ini_set('memory_limit', '1280M');

class MoveCompPNFrom2ndEditorToNext extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'moveCompPNFrom2ndEditorToNext';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'for CompPN assignments, close second editor open assignments and move to next step';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
		$atoms = [];
		$domainIds = [];
		$timestamp = (new Atom())->freshTimestampString();

		$domains = Domain::where('product_id', '=', 13)
                ->orderBy('sort', 'ASC')
				->get();

		foreach ($domains as $domain){
				$domainIds[$domain['code']]= $domain['id'];
		}

        $entities = self::_organizeAtoms(self::_getAtomList());
        $count = 0;
        foreach($entities as $entityId => $versionIds) {
            //find out the assignment of the atom
            $current_assignment =
            Assignment::where('atom_entity_id', '=', $entityId)
                ->where('task_id', '=', 1302) //1302: second editor review content
                ->whereNull('task_end')
                ->get()
                ->last();

            if (!empty($current_assignment)){
                $current_assignment->task_end = $timestamp;
                $current_assignment->save();
                //find out the domain of atom
                $atoms = Atom::where('entity_id', '=', $entityId)
                            ->orderBy('id')->get();
                $atom = $atoms->last();
                preg_match_all('/<content_area>\s*(\r\n|\n|\r)*\s*<entry>(.*)<\/entry>\s*(\r\n|\n|\r)*\s*<\/content_area>/Si', $atom->xml, $matches);
                $uniqueDomains = array_unique($matches[2]);
                $atomDomainIds = [];
                foreach ($uniqueDomains as $uniqueDomain){
                    if ($uniqueDomain !== ' ' && $domainIds[$uniqueDomain]){
                        $atomDomainId = $domainIds[$uniqueDomain];
                        array_push($atomDomainIds, $atomDomainId); 
                    }
                }
                sort($atomDomainIds);
                $userIds = self::_getUserIds($uniqueDomains, $domainIds, 1303);//1303 is associate editor group id
                if (!empty($userIds)){
                    foreach ($userIds as $userId){
                        self::_makeAssignment($entityId, $userId, 1303); //1303 : associate editor review content
                        $count++;
                        echo 'assigned to associate editor ' .$entityId. ' ' .$atom->alpha_title. ' ' . $count. ' '. implode($uniqueDomains)."\n";
                    }
                }elseif ($atomDomainIds == [1347] || $atomDomainIds == [1539] || $atomDomainIds == [1347, 1539]){
                    //testlets(1539) and culture awareness (1347)
                    if ($atom->id%2 == 0){
                        self::_makeAssignment($entityId, 603, 1309); //1309 : Author perform as associate editor 603: author user_id
                        $count++;
                        echo 'assigned to author ' .$entityId. ' ' .$atom->alpha_title. ' ' . $count. ' '. implode($uniqueDomains)."\n";
                    }else{
                        self::_makeAssignment($entityId, 604, 1309); //1309 : Author perform as associate editor  604: author user_id
                        $count++;
                        echo 'assigned to author ' .$entityId. ' ' .$atom->alpha_title. ' ' . $count. ' '. implode($uniqueDomains)."\n";
                    }
                }
                else{ //assign to two associate editor alternatively, if no domain in xml
                    if ($atom->id%2 == 0){
                        self::_makeAssignment($entityId, 602, 1303); //1303 : associate editor review content   602: associate editor user_id
                        $count++;
                        echo 'assigned to associate editor, no domain ' .$entityId. ' ' .$atom->alpha_title. ' ' . $count. ' '. implode($uniqueDomains)."\n";
                    }else{
                        self::_makeAssignment($entityId, 614, 1303); //1303 : associate editor review content   614: associate editor user_id
                        $count++;
                        echo 'assigned to associate editor, no domain ' .$entityId. ' ' .$atom->alpha_title. ' ' . $count. ' '. implode($uniqueDomains)."\n";
                    }
                }
            }
        }
    }

    protected static function _getAtomList() {
        return Atom::select(['id', 'entity_id'])->where('product_id', '=', 13)->orderBy('id')->get();
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
        $existing_assignments =
            Assignment::where('atom_entity_id', '=', $entityId)
                ->where('task_id', '=', $taskId)
                ->where('user_id', '=', $userId)
                ->whereNull('task_end')
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