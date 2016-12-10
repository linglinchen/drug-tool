<?php

/* Change section type to be intravenous from none for IV records
 *
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

			 /*$sql = 'SELECT id, entity_id, title, UNNEST(XPATH(\'//monograph[@status="discontinued"]/mono_name/text()\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', xml, \'</root>\')))) AS subtitle
                FROM atoms
                WHERE id IN(' . self::buildLatestIDQuery()->toSql() . ')
                    AND product_id=' . $productId . '
                    AND XPATH_EXISTS(\'//monograph[@status="discontinued"]\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', xml, \'</root>\')))';*/
//<section type="none" id="s51">
							//<sec_title>IV route</sec_title>

					$sql = ("select id, xml from atoms 
            				where id IN (" . Atom::buildLatestIDQuery()->toSql() . ")
            				AND cast (xpath('//section[@type=\"doses\"]//sec_title[contains(., \"Administer\")]/parent::section//sec_title[contains(., \"IV\") and not(preceding-sibling::label)]', xml::xml) as text[])  != '{}'
							limit 2");
        $atoms = DB::select($sql);
		$atoms_arr = json_decode(json_encode($atoms), true);
		foreach($atoms_arr as $atom) {
			$xml = $atom['xml'];
			//print_r($atom['id']); exit;
			if (preg_match('/(<section type="none".*>\n\t+<sec_title>.*IV\:?.*<\/sec_title>)/', $xml, $match)){
			//if (preg_match('/<section type="none".*\>\t+<sec_title>.*IV.*<\/sec_title>/', $xml)){
				print_r($match); exit;
			}

            //$newXml = preg_replace(array_keys('/<section type="none".*\>\t+<sec_title>.*IV.*</sec_title>/'), array_values('<section type="intravenous"'), $atom->xml, -1, $count);
			/*if($newXml != $atom->xml) {
                $newAtom = $atom->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
               // $newAtom->save();
			}*/
		}
			



        /* output messages */
 

    }
}