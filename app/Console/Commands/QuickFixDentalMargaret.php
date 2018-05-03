<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Atom;
use App\Assignment;
ini_set('memory_limit', '1280M');

class QuickFixDentalMargaret extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:dentalMargaret';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'close Margaret 556 task (if she is the only author), and open a 557 for her';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $file = fopen("closeMargaret556.csv", "w") or die("Unable to open file!");
        $atoms = [];
        $timestamp = (new Atom())->freshTimestampString();

        $entities = self::_organizeAtoms(self::_getAtomList());
        foreach($entities as $entityId => $versionIds) {
            $atoms = Atom::where('entity_id', '=', $entityId)
                        ->get();
            $atom = $atoms->last();
            if ($atom->status_id != 4300){

            //check if the atom has been assigned
                $assignments = Assignment::where('atom_entity_id', '=', $entityId)
                                        ->where('task_id', '=', 556) //author revision
                                        ->get();


                if (sizeof($assignments) > 0){
                    $openAssignments = Assignment::where('atom_entity_id', '=', $entityId)
                                        ->where('task_id', '=', 556) //author revision
                                        ->where('task_end', '=', null)
                                        ->get();
                    if (sizeof($openAssignments) == 1 && $openAssignments[0]->user_id == 241){ //Margaret is the only author
                        $openAssignments[0]->task_end = $timestamp; //close the Margaret's 556 assignment
                        $openAssignments[0]->save();

                        $newAssignment = [
                            'atom_entity_id' => $entityId,
                            'user_id' => 241,
                            'task_id' => 557,
                            'task_end' => null
                        ];
                        Assignment::query()->insert($newAssignment);
                        fwrite($file, $entityId."\tprocessed\n");
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