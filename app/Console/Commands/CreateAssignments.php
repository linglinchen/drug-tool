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
                'atomEntityId' => $entityId,
                'userId' => 2,
                'taskId' => 3,
                'taskEnd' => null
            ];

            if(sizeof($versionIds) > 1) {
                $assignment2 = [
                    'atomEntityId' => $entityId,
                    'userId' => 5,
                    'taskId' => 4,
                    'taskEnd' => null
                ];
                $assignment1['taskEnd'] = $timestamp;

                $assignments[] = $assignment1;
                $assignments[] = $assignment2;

                $atoms = Atom::where('entityId', '=', $entityId)->get();
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
        return Atom::select(['id', 'entityId'])->get();
    }

    protected static function _organizeAtoms($list) {
        $output = [];
        foreach($list as $row) {
            if(!isset($output[$row['entityId']])) {
                $output[$row['entityId']] = [];
            }

            $output[$row['entityId']][] = $row['id'];
        }

        return $output;
    }
}
