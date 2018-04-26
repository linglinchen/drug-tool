<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Atom;
use App\Assignment;
ini_set('memory_limit', '1280M');

class QuickFixAssignToMargaret extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:assignToMargaret';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'close the assignment if it is not asigned to Margaret/pharm1/pharm2, atoms without assignment will be assigned to Margaret';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $file = fopen("../dentalAssignToMargaret.csv", "w") or die("Unable to open file!");
        $atoms = [];
        $timestamp = (new Atom())->freshTimestampString();

        $entities = self::_organizeAtoms(self::_getAtomList());
        foreach($entities as $entityId => $versionIds) {
            $atoms = Atom::where('entity_id', '=', $entityId)
                        ->get();
            $atom = $atoms->last();
            if ($atom->status_id != 4300){

            //check if the atom has been assigned
                $openAssignments = Assignment::where('atom_entity_id', '=', $entityId)
                                        ->where('task_end',
                                        '=', null)->get();

                $newAssignment = [
                    'atom_entity_id' => $entityId,
                    'user_id' => 241,
                    'task_id' => 557,
                    'task_end' => null
                ];

                if (sizeof($openAssignments) == 0){  //the atom doesn't have an open assignment
                    fwrite($file, $entityId."\tno open assignment\n");
                    //Assignment::query()->insert($newAssignment);
                }
                else{ //atom with open assignments
                    if (count($openAssignments) == 1){ //only one open assignment
                        $openAssignment = $openAssignments[0];
                        $userId = $openAssignment->userId;
                        if ($userId != 241 && $userId != 533 && $userId != 534){ //not assigned to Margaret/pharm1/pharm2
                            $openAssignment->task_end = $timestamp;
                            fwrite($file, $entityId."\tclosed assignment, single\n");
                            //$openAssignment->save();
                        // Assignment::query()->insert($newAssignment);
                        }else{
                            fwrite($file, $entityId."\tdo nothing\n");
                        }
                    }else{ //multiple open assignments
                        foreach ($openAssignments as $openAssignment){
                            $openAssignment->task_end = $timestamp;     //close the each assignment
                            //$openAssignment->save();
                        }
                        fwrite($file, $entityId."\tclosed assignment, multiple\n");
                        //Assignment::query()->insert($newAssignment);
                    }
                }
            }
        }
        fclose($file);
    }

    protected static function _getAtomList() {
        return Atom::select(['id', 'entity_id'])->where('product_id', '=', 5)->get();
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
