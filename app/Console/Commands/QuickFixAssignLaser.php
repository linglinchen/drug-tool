<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Domain;
use App\Assignment;
use Illuminate\Support\Facades\DB;
ini_set('memory_limit', '1280M');

class QuickFixAssignLaser extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:assignLaser';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make appropriate assignments for terms that contain domain LASER (either as main or subdomain)';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $atoms = [];
        $timestamp = (new Atom())->freshTimestampString();
        $atoms = self::_getAtomList();
        foreach($atoms as $atom) {
            $existing_assignments = Assignment::where('atom_entity_id', '=', $atom['entity_id'])
                                             ->where('task_id', '=', 556)
                                             ->whereNull('task_end')
                                             ->get();
            if (sizeof($existing_assignments) > 0){  // if there is open 556 tasks
                if (self::_notAssignedToLaser($existing_assignments)){ // if Laser contributor has not been assigned
                    $assignment = [
                        'atom_entity_id' => $atom['entity_id'],
                        'user_id' => 513,
                        'task_id' => 556,
                        'task_end' => null,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                        'created_by' => 200
                    ];
                    //Assignment::query()->insert($assignment);
                    echo "assigned to LASER contributor for $atom[entity_id]\n";
                }
            } else {
                echo "No open task (556): $atom[entity_id]\n";
            }
        }
    }

    protected static function _getAtomList() {
        $sql = "select entity_id, alpha_title from atoms
            where id in 
            (select MAX(id) from atoms group by entity_id) 
            and deleted_at is null 
            and xml like '%LASER</category>%'
            AND product_id = 5";

        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);
        //var_dump($atomsArray);
        return $atomsArray;
    }

    protected static function _notAssignedToLaser($existing_assignments){
        $flag = 1;
        foreach ($existing_assignments as $existing_assignment){
            if ($existing_assignment['user_id'] == 513){
                $flag = 0;
            }
        }
        return $flag;
    }
}
