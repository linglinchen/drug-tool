<?php

/* Remove extra tailing spaces inside <usage> for Nursing

    Remove <emphasis> inside <usage>
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Product;

class QuickFixAddFullCreditLineNursing extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:addfullcreditline'; //php artisan quickfix:addfullcreditline

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command add <fullcredit> tag into xml based on the file provided';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $creditInfo = self::_getFullCredit();
        foreach ($creditInfo as $title => $item){
            foreach ($item as $shortCredit => $longCredit){
                self::_updateXml($title, $shortCredit, $longCredit);
            }
        }
    }

    public static function _getFullCredit() {
        $filename = base_path() . '/data/fullcredit_nursing.txt';
        if(!file_exists($filename)) {
            return;
        }

        $info = [];
        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));
        $headers = array_shift($lines);  
        //parse the lines as csv
        foreach($lines as $line) {
            $fields = explode("\t", $line);
            $title = trim($fields[0], '"');
            $atomId = trim($fields[1], '"'); 
            $figureSource = trim($fields[2], '"'); 
            $figureCaption = trim($fields[3], '"'); 
            $shortCredit = trim($fields[4], '"'); 
            $longCredit = trim($fields[6], '"'); 

            if ($title == '' || $shortCredit == '' || $longCredit ==''){
                echo "missing info: $line\n";
            }
           // $info[$title]['atomId'] = $atomId;
            if (!isset($info[$title][$shortCredit])){
                $info[$title][$shortCredit] = $longCredit;
            }
        }
        return $info;
    }

    public static function _updateXml($title, $short, $long) {
        ini_set('memory_limit', '1280M');
        $atom = Atom::whereIn('id', function ($q) {
                    Atom::legacyBuildLatestIDQuery(null, $q);
                })->where('product_id','=', 8)
                ->where('alpha_title', '=', $title)
                ->get()->last();
        $atomArr = array($atom);
        if ($atomArr[0] == ''){
            echo "No atom found: title: $title, short: $short, long: $long\n";
        }
        else{
            $xml = $atom->xml;

            $pattern = '/<credit>'.preg_quote($short).'<\/credit>/';
            preg_match($pattern, $xml, $match); 
            if (isset($match) && isset($match[0])){
                //$match[0] is <credit>AACN,  2008</credit>
                $replacement = "<credit>$short</credit><fullcredit>$long</fullcredit>";
                $newXml = preg_replace($pattern, $replacement, $xml);

                if(isset($newXml) && ($newXml !== $atom->xml)) {
                    $newAtom = $atom->replicate();
                    $newAtom->xml = $newXml;
                    $newAtom->modified_by = null;
                    //$newAtom->save();
                }
                else{
                    echo "failed to add fullcredit: title: $title, short: $short, long: $long\n";
                }
            }
            else{
                echo "No match found: title: $title, short: $short, long: $long\n";
            }
        }
    }
}