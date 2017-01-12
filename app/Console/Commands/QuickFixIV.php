<?php

/**
 * Change section type to be intravenous from none for IV records
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;

class QuickFixIV extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:iv';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command changes section type to be intravenous from none for IV records.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        self::fixIV();
    }

    public static function fixIV() {

        $sql = "select id, alpha_title, xml from atoms 
            where id IN (" . Atom::buildLatestIDQuery()->toSql() . ")
            AND cast (xpath('//section[@type=\"doses\"]//sec_title[contains(., \"Administer\")]/parent::section//sec_title[contains(., \"IV\") and not(preceding-sibling::label)]', xml::xml) as text[])  != '{}'";
        
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);
        $changed = 0;
        foreach($atomsArray as $atom) {
            $xml = $atom['xml'];
            $atomModel = Atom::find($atom['id']);

            //add this header to xml so later processing won't do unwanted encoding, e. g. change '-' to &#x2014
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'.$xml;
            $xmlObject = simplexml_load_string($xml); 

            //get the section that is parent of "IV"
            $sections = $xmlObject->xpath('//section[@type="doses"]//sec_title[contains(., "Administer")]/parent::section//sec_title[contains(., "IV") and not(preceding-sibling::label)]/parent::section');

            foreach ($sections as $section){
                $attributes = $section->attributes();

                if ($attributes->type == 'none'){
                    $attributes->type = 'intravenous';
                }
            }

            $xmlString = $xmlObject->asXML();
        
            //remove the header line that's generated by asXML()
            $newXml = preg_replace('/<\?xml version="1\.0" encoding="UTF-8"\?>\n/', '', $xmlString);

            if($newXml !== $atomModel->xml) {
                $newAtom = $atomModel->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
                $changed++;
                $newAtom->save();
            }
        }

        /* output messages */
        echo 'total atoms changed: '.$changed."\n";
    }
}