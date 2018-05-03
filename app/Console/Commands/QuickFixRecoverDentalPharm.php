<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Atom;
use App\Assignment;
ini_set('memory_limit', '1280M');

class QuickFixRecoverDentalPharm extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:recoverDentalPharm';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'reopen the assignments that asigned to Margaret/pharm1/pharm2, task_id=556';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $file = fopen("../dentalPharmRecover.csv", "w") or die("Unable to open file!");
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
                                        ->where('task_end','>=', '2018-04-27 02:00:00')
                                        ->where('task_end','<=', '2018-04-27 02:34:00')
                                        ->where('task_id', '=', 556) //author revision
                                        ->where(function($q){
                                            $q->where('user_id', '=', 533) //Pharm1
                                              ->orWhere('user_id', '=', 534) //Pharm2
                                              ->orWhere('user_id', '=', 241); //Margaret
                                        })
                                        ->get();


                if (sizeof($assignments) > 0){  
                    fwrite($file, $entityId."\treopened\n");
                    $toBeDeleted = Assignment::where('atom_entity_id', '=', $entityId)
                                        ->where('created_at','>=', '2018-04-27 02:00:00')
                                        ->where('created_at','<=', '2018-04-27 02:34:00')
                                        ->where('user_id', '=', 241)  //Margaret
                                        ->where('task_id', '=', 557)  //editor-in-chief review
                                        ->get();
                    if (sizeof($toBeDeleted) == 1){ //delete this record
                        //$toBeDeleted[0]->delete();
                        foreach ($assignments as $assignment){
                            $assignment->task_end = null;     //open each assignment
                            //$assignment->save();
                    }

                    }else if (sizeof($toBeDeleted) > 1){
                        echo "check: $entityId has more than one Margaret assignments";
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
