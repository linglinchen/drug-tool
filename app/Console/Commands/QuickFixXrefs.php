<?php
/* 
    set the sort order of monographs as they were originally imported
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Product;
use Illuminate\Support\Facades\DB;

class QuickFixXrefs extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:xrefs {productId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command swaps in the necessary internal entityId links (format <xref refid="a:entityId"> for any placeholder links (format refid="tra_..."). Run this on newly imported chapters that have such placeholders.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
	    $productId = (int)$this->argument('productId');
        if(!$productId || !Product::find($productId)) {
            throw new \Exception('Invalid product ID.');
        }
        $this->productId = $productId;
        self::_fixNonEnityIdRefs($productId);
    }
	 



    public static function _fixNonEnityIdRefs($productId) {
       $sql = "select a.*, b.id as targetid, b.entity_id as target_entityid
					from (
					select max(a.id) as sourceid, a.entity_id, t.xmlrefsnippet, a.xml, a.product_id
					  from (select id,
					  regexp_replace(
					  unnest(xpath('//xref/@refid', a.xml::xml))::varchar(255) 
					  , '\#.*$', '')
					  as xmlrefsnippet 
					  from atoms a) t
					  inner join atoms a
						on a.id = t.id      
					group by a.entity_id, t.xmlrefsnippet, a.xml, a.product_id) a
						inner join atoms b 
							on a.xmlrefsnippet = 'tra_'||lower(regexp_replace(b.alpha_title, '\W', '', 'g'))
						where a.product_id=$productId and b.product_id=$productId;
        ";
		
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);  //convert object to 
//watcher array to track which records from atoms results set have been processed and control the update of the xml so that all replacements on a sourceid are done before the sourceid record is saved. Otherwise, xml could be written each time a row for substition is done.
		$watcherArray=array();
			foreach($atomsArray as $keyInt=>$val){
				//key for watcher will be the atom's id (atom.sourceid)
				$newKey=$val['sourceid'];
				$watcherArray[$newKey][] = ['atomkey'=>$keyInt, 'originalxml'=>$val['xml'],'newXml'=>NULL];

			}
//$watcherArray = sort($watcherArray);
//print_r ($watcherArray['51311']);

        $totalDetectedAtoms = sizeof($watcherArray);
        $changedAtoms = 0;
        $changedXref = 0;
 //add working atom array, loop through. Issue now is how to do all needed replacements for the atom, then save the atom, then move on to next (otherwise, could get atom saved multiple times for every single xref. Add a watcher? key value pairs?
         foreach($atomsArray as $atom) {
			$atomModel = Atom::find($atom['sourceid']);
//			print_r($atomModel);
            $xml = $atomModel->xml; 
            $xml = $atom['xml'];
			$find = '/"'.$atom['xmlrefsnippet'].'([#"])/';
			$replace = '"a:'.$atom['target_entityid'].'$1';
			preg_replace($find, $replace, $xml, -1, $count);
			
			$changedXref = $changedXref + $count;
 
   

/*             if($newXml !== $atomModel->xml) {
                $newAtom = $atomModel->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
                $changedAtoms++;
                $newAtom->save();
            } */
        }
        /* output messages */
        echo 'affected Atoms: '.$totalDetectedAtoms."\n";
        echo 'changed Atoms: '.$changedAtoms."\n";
        echo 'total changed xrefs: '.$changedXref."\n";
    }
}