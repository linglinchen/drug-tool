<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Domain;
use App\Assignment;
use Illuminate\Support\Facades\DB;
ini_set('memory_limit', '1280M');

class QuickFixPara extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:para';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Find out para missing terms that have been reviewed and reassign them';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $sql = "SELECT DISTINCT(alpha_title), entity_id
        FROM atoms WHERE 
        cast (xpath('//entry[defgroup[para]]/headw', xml::xml) as text[])  != '{}'
        and product_id = 3 order by alpha_title";
        
        $paraMissingAtoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($paraMissingAtoms), true);  //convert object to array
        $entityIdArray=[];
        foreach ($atomsArray as $atom){
            $atomTitle = $atom['alpha_title'];
            array_push($entityIdArray, $atom['entity_id']);
        }

        $assignments = Assignment::wherein('atom_entity_id', $entityIdArray)
            ->join('atoms', 'assignments.atom_entity_id', '=', 'atoms.entity_id')
            ->where('task_id', '=', 25)->whereNotNull('task_end')->get()->toArray();

        foreach ($assignments as $assignment){
            echo $assignment['domain_code']."\t".$assignment['alpha_title']."\n";
            $new_assignment = [
                'atom_entity_id' => $assignment['atom_entity_id'],
                'user_id' => $assignment['user_id'],
                'task_id' => 25,
                'task_end' => null
            ];
            Assignment::query()->insert($new_assignment);
        }
    }
}
