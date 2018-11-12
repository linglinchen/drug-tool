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
			$uniques = array_unique($matches[1]);
			foreach ($uniques as $unique){
				if ($unique !== ' ' && $domainIds[$unique]){
					$domainId = $domainIds[$unique];
					$userIds = UserDomain::getUserIds($domainId,803);
				}
			}


            if ($atom->domain_code){   //if the atom has a domain
                $domain = Domain::where('code', '=', $atom->domain_code)->get()->last();
                $reviewer_id = $domain->reviewer_id;
                if (!empty($reviewer_id)){  //if this domain has an reviewer assigned
                    $assignment = [
                        'atom_entity_id' => $entityId,
                        'user_id' => $domain->reviewer_id,
                        'task_id' => 25,
                        'task_end' => null
                    ];

                    //check if the atom has been assigned
                    $existing_assignments = Assignment::where('atom_entity_id', '=', $entityId)
                                            ->where('task_id', '=', 25)->get()->last();
                    if (is_null($existing_assignments)){
                        Assignment::query()->insert($assignment);
                    }
                }
            }else{
                echo $atom->title."\n";
            }
        }
    }

    protected static function _getAtomList() {
        return Atom::select(['id', 'entity_id'])->where('product_id', '=', 8)->get();
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