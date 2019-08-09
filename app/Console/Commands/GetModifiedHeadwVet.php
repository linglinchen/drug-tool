<?php

/* search for atoms that has been edited
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Product;
use App\Status;

class GetModifiedHeadwVet extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'get:modifiedHeadwVet {productId}}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command find out vet headwords that have been changed ,  e.g. get:modifiedHeadwVet 3';

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

        self::_getModified($productId);
    }

    public static function _getHeadwDef($h){
        $headwDef = '';
        if (is_array($h) && isset($h['deftext'])){
            if (is_array($h['deftext'])){
                $headwDef = implode('|', $h['deftext']);
            }
            else{
                $headwDef = $h['deftext'];
            }
        }
        else if (is_array($h) && isset($h['xref'])){
            if (is_array($h['xref'])){
                $headwDef = end($h['xref']);
            }
            else{
                $headwDef = $h['xref'];
            }
        }
        else if (is_array($h) && isset($h['para'])){
            if (is_array($h['para'])){
                $headwDef = implode('|', $h['para']);
            }
            else{
                $headwDef = $h['para'];
            }
        }
        else{
            if (is_array($h)){
                $headwDef = end($h);
                if (is_array($headwDef)){
                    $headwDef = end($headwDef);
                    if (is_array($headwDef)){
                        $headwDef = end($headwDef);
                        if (is_array($headwDef)){
                            $headwDef = end($headwDef);
                        }
                    }
                }
            }else{
                $headwDef = $h;
            }
        } //print_r($headwDef); echo "\n";
        return $headwDef;
    }

    public static function _getInfoArr($atomXml){
        $infoArr = [];
        $atomXml = preg_replace('/<\/?emphasis[^>]*>/i', '', $atomXml);
        $atomXml = preg_replace('/<\/?xref[^>]*>/i', '', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext><deftext><deftext><deftext><deftext><deftext><deftext><deftext>([^>]*)<\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext><deftext><deftext><deftext><deftext><deftext><deftext>([^>]*)<\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext><deftext><deftext><deftext><deftext><deftext>([^>]*)<\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext><deftext><deftext><deftext><deftext>([^>]*)<\/deftext><\/deftext><\/deftext><\/deftext><\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext><deftext><deftext><deftext>([^>]*)<\/deftext><\/deftext><\/deftext><\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext><deftext><deftext>([^>]*)<\/deftext><\/deftext><\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext><deftext>([^>]*)<\/deftext><\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);
        $atomXml = preg_replace('/<deftext><deftext>([^>]*)<\/deftext><\/deftext>/i', '<deftext>$1</deftext>', $atomXml);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.$atomXml;
        $xmlObject = simplexml_load_string($xml);
        $headwords = $xmlObject->xpath('//entry'); //print_r($headwords);
        foreach ($headwords as $headword){
            $headw = json_decode(json_encode($headword), true);
            if (isset($headw['headw'])){
                if (is_array($headw['headw'])){
                    $headwText = end($headw['headw']);
                }else{
                    $headwText = $headw['headw'];
                }
            }
            else{
                $headwText = '';
            }
            $headwDef = '';  //could have 9 deftext
            if (isset($headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext']['deftext'])){
                $head = $headw['defgroup']['def']['deftext']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def']['deftext'])){
                $head = $headw['defgroup']['def']['deftext'];
                $headwDef = self::_getHeadwDef($head);
            }
            else if (isset($headw['defgroup']['def'])){
                $head = $headw['defgroup']['def'];
                $headwDef = self::_getHeadwDef($head);
            }

            $headwDef = is_array($headwDef) ? implode('|', $headwDef) : $headwDef;
            $infoArr[$headwText] = $headwDef;
        }
        return $infoArr; 
    }

    public static function _getModified($productId) {

         // getting the original imported atoms
         $sql = "SELECT id, entity_id, alpha_title, xml
            FROM atoms
            WHERE 
		        product_id = $productId
                AND id in 
                (
                    SELECT MIN(id)
                    FROM atoms 
                    WHERE product_id = $productId
                    GROUP BY entity_id
                ) and molecule_code IN ('A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K', 'L', 'M', 'N', 'O')
                AND created_at < '2017-05-26 20:20:20'
            ORDER BY molecule_code, sort
        ";

        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);  //convert object to array
        
        $modified = 0;
        $new =0;
        $headw_array = [];
        foreach($atomsArray as $oriAtom) {
            $currentAtoms = Atom::where('entity_id', '=', $oriAtom['entity_id'])
            ->orderBy('id')->get();
            if (sizeof($currentAtoms) > 0){
                $currentAtom = $currentAtoms->last();
                print_r($currentAtom->entity_id); echo "\t"; print_r($currentAtom->alpha_title); echo "\t";
                if ($currentAtom->status_id == 2200 && $currentAtom->molecule_code !== NULL
                    && $currentAtom->id != $oriAtom['id']){
                    $oriInfo = [];
                    $currentInfo = [];
                    $oriInfo = self::_getInfoArr($oriAtom['xml']);
                    $currentInfo = self::_getInfoArr($currentAtom->xml);
                    foreach ($currentInfo as $h => $def){
                        print_r($h);
                        echo "\t";
                        if (isset($oriInfo[$h])){ //existing headword, need to check if def changed.
                            if (strcmp($oriInfo[$h], $currentInfo[$h]) !== 0){ // different
                                $modified++;
                                //print_r($oriInfo[$h]); echo "\n";
                                //print_r($currentInfo[$h]); echo "\n\n";
                            }
                        }
                        else{
                            $new++;
                        }
                    }
                }
            }
            echo "\n";
        }

        echo "new headw: $new\n";
        echo "modified headw: $modified\n";
    }
}