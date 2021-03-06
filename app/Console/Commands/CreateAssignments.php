<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Assignment;

class CreateAssignments extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'createassignments';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make appropriate assignments for Craig Roth across all monographs, and update atom statuses';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $atoms = [];
        $assignments = [];
        $timestamp = (new Atom())->freshTimestampString();

        $entities = self::_organizeAtoms(self::_getAtomList());
        foreach($entities as $entityId => $versionIds) {
            $assignment1 = [
                'atom_entity_id' => $entityId,
                'user_id' => 2,
                'task_id' => 3,
                'task_end' => null
            ];

            if(sizeof($versionIds) > 1) {
                $assignment2 = [
                    'atom_entity_id' => $entityId,
                    'user_id' => 5,
                    'task_id' => 4,
                    'task_end' => null
                ];
                $assignment1['task_end'] = $timestamp;

                $assignments[] = $assignment1;
                $assignments[] = $assignment2;

                $atoms = Atom::where('entity_id', '=', $entityId)->get();
                $atom = $atoms->last()->replicate();
                $atom->statusId = 100;
                $atom->save();
            }
            else {
                $assignments[] = $assignment1;
            }
        }

        Assignment::query()->insert($assignments);
    }

    protected static function _getAtomList() {
        return Atom::select(['id', 'entity_id'])->get();
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
