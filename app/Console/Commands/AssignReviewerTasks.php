<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Domain;
use App\Assignment;

class AssignReviewerTasks extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'assignreviewertasks';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make appropriate assignments for reviewers across all words based on domains';

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
            //find out the domain of atom
            $atoms = Atom::where('entity_id', '=', $entityId)
                        ->get();
            $atom = $atoms->last();

            $domain = Domain::where('code', '=', $atom->domain_code)->get()->last();
            $reviewer_id = $domain->reviewer_id; echo $atom->title.' '.$domain->code.' '.$domain->reviewer_id."\n";
            if (!empty($reviewer_id)){  //if this domain has an reviewer assigned
                $assignment = [
                    'atom_entity_id' => $entityId,
                    'user_id' => $domain->reviewer_id,
                    'task_id' => 25,
                    'task_end' => null
                ];
                $assignments[] = $assignment;
            }
        }

       // Assignment::query()->insert($assignments);
    }

    protected static function _getAtomList() {
        return Atom::select(['id', 'entity_id'])->where('product_id', '=', 3)->get();
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
