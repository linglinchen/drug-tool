<?php

/* search for the vet terms that xref is needed based on previous transformation (it's missing from recent transformation)
 * insert <xref> to them
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Product;
use App\Status;

class QuickFixVetXref extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:vetXref';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command searches for vet terms that has missing <xref> tags based on a older transformation xml and insert the <xref> tags into database atoms xml e.g. quickfix:vetXref';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        ini_set('memory_limit', '1280M');
       $xrefLocation = self::_getXrefBasedOnOldXml();
       $entityIdArray = self::_getEntityIdArray();

       //insert <xref> into the xml
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->where('product_id','=', 3)->get();

        foreach($atoms as $atom) {
            foreach ($xrefLocation as $xref){
                foreach ($xref as $term => $ref){
                    if ($atom->alpha_title == $term && $atom->alpha_title == 'A-mode'){ //if atom has xref
                        $newAtom = $atom->replicate();
                        $newXml = $atom->xml;
                        foreach ($ref as $refered){
                            if (array_key_exists($refered, $entityIdArray)){
                                $entityId = $entityIdArray[$refered];
                                echo "$term\t$refered\t$entityId\n";
                                $referedPattern = '/(<xref refid="tra__REFID__">)?'.$refered.'(</xref>)?/';
                                $replacement = '<xref refid="a:'.$entityId.'">'.$refered.'</xref>';
                                $newXml = preg_replace($referedPattern, $replacement, $newXml);
                            }
                        }
                        $newAtom->xml = $newXml;
                        $newAtom->modified_by = null;
						$newAtom->save();
                        exit;
                    }
                }
            }
        }

        // $xml = '<entry sortorder="758" type="shared">
		// 		<headw id="hw_REPLACE_ME__">hepatoprotectant</headw>
		// 		<category cat_id="cat_REPLACE_ME__">PHTHG</category>
		// 		<defgroup type="shared">
		// 			<def id="d_REPLACE_ME__" n="1">compounds that may provide the liver with some protection from toxins or biochemical injury. Includes ursodeoxycholic acid, <emphasis style="italic">S</emphasis>-adenosyl-<emphasis style="smallcaps">l</emphasis>- methionine, and silymarin.    Many are nutraceuticals with uncertain efficacy.</def>
		// 		</defgroup>
		// 	</entry>';
        // $term = 'hepatoprotectant';
        // $ref=['silymarin', 'S-adenosyl-l- methionine'];
        // $newXml = $xml;
        // foreach ($ref as $refered){
        //     if (array_key_exists($refered, $entityIdArray)){
        //         $entityId = $entityIdArray[$refered];
        //         echo "$term\t$refered\t$entityId\n";
        //         $referedPattern = '/(<xref refid="tra__REFID__">)?'.$refered.'(<\/xref>)?/';
        //         $replacement = '<xref refid="a:'.$entityId.'">'.$refered.'</xref>';
        //         $newXml = preg_replace($referedPattern, $replacement, $newXml);
        //         echo "$newXml\n";
        //     }
        // }
    }

    public static function _getEntityIdArray() {
        $entityIdArray = [];
        $atoms = Atom::whereIn('id', function ($q) {
                        Atom::buildLatestIDQuery(null, $q);
                    })->where('product_id','=', 3)->get();

        foreach($atoms as $atom) {
            $entityIdArray[$atom->alpha_title] = $atom->entity_id;
            //echo "$atom->alpha_title\t$atom->entity_id\n";
        }
        return $entityIdArray;
    }

    public static function _getXrefBasedOnOldXml() {
        $dir = "/var/www/vet_batch_2017_05_19";
        $files = scandir($dir);
        $files = array_slice($files, 2);
        $xref_arr = [];
        foreach ($files as $file){
            //if ($file == 'letter_a_4e.xml'){
            $xml = file_get_contents($dir.'/'.$file);
            $doc = new \DOMDocument();
            $doc->loadXML($xml);
            $xpath = new \DOMXpath($doc);
            $chapterElements = $xpath->query('/dictionary/body/alpha/entry');
            foreach($chapterElements as $chapterElement) {
                $headw = '';
                $ref_array = [];
                $ref_unique = [];
                foreach($chapterElement->childNodes as $atomNode) {
                    if(isset($atomNode->tagName) && ($atomNode->tagName == 'headw')) {
                        $headw = trim(str_replace("\n", '', $atomNode->nodeValue));
                    }else{
                        $xml = $doc->saveXML($atomNode);
                        if (strlen(trim($xml)) > 0 && strlen($headw) > 0){
                            $termDoc = new \DOMDocument();
                            $termDoc->loadXML($xml);
                            $termXpath = new \DOMXpath($termDoc);
                            $xrefElements = $termXpath->query('//xref');
                            foreach ($xrefElements as $xrefElement){
                                $xrefText = trim(str_replace("\n", '',$xrefElement->textContent));
                                if (strlen($xrefText)>0){
                                    array_push($ref_array, $xrefText);
                                }else{
                                    //echo "self closing xref: $headw\n";
                                }
                            }
                        }
                    }
                }
                $ref_uniq = array_unique($ref_array);
                if ($ref_uniq){
                    array_push($xref_arr, array($headw => $ref_uniq));
                }
                    /* preg_match_all('/<xref refid="tra__REFID__"( id_legacy="[\d]+")?>(.*)<\/xref>/Si', $xml, $matches);
                     preg_match_all('/<xref refid="tra__REFID__">([\d\D]*)<\/xref>/Si', $xml, $matches);*/
            }
            //}
        }
        $xref_arr = array_unique($xref_arr, SORT_REGULAR);
        echo "term\trefered\n";
        foreach ($xref_arr as $xref){
            foreach ($xref as $term => $ref){
                //if ($term == 'hepatoprotectant'){
                    foreach ($ref as $refered){
                        //echo "$term\t$refered\n";
                    }
                //}
            }
        }
        return $xref_arr;
    }
}