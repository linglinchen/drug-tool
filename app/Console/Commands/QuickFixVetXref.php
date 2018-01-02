<?php

/* search for the vet terms that xref needed (it's missing from recent transformation)
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
       $xrefLocation = self::_getXrefBasedOnOldXml();
       $entityIdArray = self::_getEntityIdArray();

    }

    public static function _getEntityIdArray() {
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->get();

        foreach($atoms as $atom) {
            echo "$atom->alpha_title\t$atom->entity_id\n";
        }
        return true;
    }

    public static function _getXrefBasedOnOldXml() {
        $dir = "/var/www/vet_batch_2017_05_19";
        $files = scandir($dir);
        $files = array_slice($files, 2);
        $xref_arr = [];
        foreach ($files as $file){
            //$file = 'sarcoptic_mange.xml';
            $xml = file_get_contents($dir.'/'.$file);
            $doc = new \DOMDocument();
            $doc->loadXML($xml);
            $xpath = new \DOMXpath($doc);
            $chapterElements = $xpath->query('//entry');
            foreach($chapterElements as $chapterElement) {
                foreach($chapterElement->childNodes as $atomNode) {
                    $headw = '';
                    $ref_array = [];
                    if(!isset($atomNode->tagName)) {
                        continue;
                    }

                    $xml = $doc->saveXML($atomNode);
                    /* preg_match_all('/<xref refid="tra__REFID__"( id_legacy="[\d]+")?>(.*)<\/xref>/Si', $xml, $matches); */
                    preg_match_all('/<xref refid="tra__REFID__">([^<]*)<\/xref>/Si', $xml, $matches);
                    if ($matches[1]){
                        foreach($matches[1] as $match){
                            array_push($ref_array, $match);
                        }
                        foreach($atomNode->parentNode->childNodes as $node){
                            if (isset($node->tagName) && $node->tagName == 'headw'){
                                //$headw = $node->textContent;
                                $headw = trim($node->nodeValue);
                            }
                        }

                        if ($headw){
                            $ref_uniq = array_unique($ref_array);
                            array_push($xref_arr, array($headw => $ref_uniq));
                        }
                    }
                }
            }
        }
        $xref_arr = array_unique($xref_arr, SORT_REGULAR);

        echo "term\trefered\n";
        foreach ($xref_arr as $xref){
            foreach ($xref as $term => $ref){
                foreach ($ref as $refered){
                    //echo "$term\t$refered\n";
                }
            }
        }

        return $xref_arr;
    }
}