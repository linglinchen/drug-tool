<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Atom;
use App\Product;

/**
 * remove the  <referenct> element from latest question atoms
 */
class QuickFixRemoveQuesReference extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:removereference for example: php artisan quickfix:removereference';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove the <reference> element from latest Question atoms xml';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        ini_set('memory_limit', '1280M');

         $sql = "SELECT MAX(id) as id, entity_id
            FROM atoms
            WHERE product_id = 7
            GROUP BY entity_id";
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);  //convert object to array

        $count = 0;
        foreach($atomsArray as $atom) {
                $atomModel = Atom::find($atom['id']);
            
                $count++;
                $newAtom = $atomModel->replicate();
                preg_match('/<reference>.*<\/reference>/', $atomModel->xml, $matches2);
                if ($matches2){
                        echo $atomModel->id."\t".$atomModel->entity_id."\t".$atomModel->alpha_title."\treference removed\n";
			            $newAtom->xml = preg_replace('/<reference>.*<\/reference>/','', $atomModel->xml);
                        $newAtom->save();
                       
                }
	}
	echo 'Total changed atoms: '.$count."\n";
    }
}
