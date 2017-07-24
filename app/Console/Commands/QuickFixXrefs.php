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
        $atomsArray = json_decode(json_encode($atoms), true);
		
//convert object to watcher array to track which records from atoms results set have been processed and control the update of the xml
//so that all replacements on a sourceid are done before the sourceid record is saved. Otherwise, xml could be written each time a row for substition is done.
		$watcherArray=array();
			foreach($atomsArray as $keyInt=>$val){
				//key for watcher will be the atom's id (atom.sourceid)
				$newKey=$val['sourceid'];
				$watcherArray[$newKey][] = ['atomkey'=>$keyInt, 'startingXml'=>$val['xml'],'WIPXml'=>NULL];
			}


        $totalDetectedAtoms = sizeof($watcherArray);
		$totalDetectedReplacements = sizeof($atomsArray);
        $changedAtoms = 0;
        $changedXref = 0;
		$count = 0; //used to increment the changedXref count
		$watchedAtomsCount = 0;

	
foreach($watcherArray as $watchedAtom) {

	 for ($i = 0; $i < sizeof($watchedAtom); ++$i){
			//grab the row from atoms array that matches the atomkey in $watcherArray index
			$currentReplacement = $atomsArray[$watchedAtom[$i]['atomkey']];
/* 						print_r('<pre>'.$currentReplacement['sourceid'] .'</pre>   ,    ');
						print_r('<pre>'.$currentReplacement['xmlrefsnippet'].'</pre> .');	 */
			
			//snippet to replace an entityid to replace it with from atomsArray
					$target_entityid = $currentReplacement['target_entityid'];
					$xmlrefsnippet = $currentReplacement['xmlrefsnippet'];
					
					//take the xml copy from the watchedAtoms array, not atoms array. watchedAtoms is the array that gets updated.
					$xmltmp = $watchedAtom[$i]['startingXml']; 

					//replace stuff and save to current $watchedAtom['newXml']
					$find = '/"'.$currentReplacement['xmlrefsnippet'].'([#"])/';
					$replace = '"a:'.$currentReplacement['target_entityid'].'$1';
					$newXml = preg_replace($find, $replace, $xmltmp, -1, $count);
					$changedXref = $changedXref + $count;

					$watchedAtom[$i]['WIPXml']	= $newXml;
					$newXmlValue = $watchedAtom[$i]['WIPXml'];

						
								if (next($watchedAtom)) {
											//replace copy current $watchedAtom['newXml'] to next 	 $watchedAtom['originalxml']
									$watchedAtom[$i+1]['startingXml'] = $newXmlValue;		

									} else {
										//commit to new atom with all replacements to database
										$atomModel = Atom::find($currentReplacement['sourceid']);
										$xml = $atomModel->xml; 
					// 					$xml = $atom['xml'];
									   if($newXmlValue !== $atomModel->xml) {
											$newAtom = $atomModel->replicate();
											$newAtom->xml = $newXmlValue;
											//print_r($currentReplacement['entity_id']);
											//print_r($newAtom['xml']);
											$newAtom->modified_by = null;
											$changedAtoms++;
											$newAtom->save();
										}			
									
									$watchedAtomsCount++;
								}

	 }					

}
			

        /* output messages */
//        echo 'affected Atoms: '.$totalDetectedAtoms."\n";
	   echo 'number of Xref replacements across atoms: '.$totalDetectedReplacements."\n";
       echo 'changed Atoms: '.$watchedAtomsCount."\n";		
//       echo 'changed Atoms: '.$changedAtoms."\n";
        echo 'total changed Crossreference instances: '.$changedXref."\n";
		}
}