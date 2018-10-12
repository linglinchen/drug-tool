<?php

/**
 * fix dental POS
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;

class QuickFixPos extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:pos';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command move <part-of-speech> out of <em> or <emphasis>.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $sql = "SELECT * FROM atoms 
                    WHERE product_id=5
	                    AND id IN 
                            (SELECT MAX(id) FROM atoms WHERE product_id=5 GROUP BY entity_id )
                            AND xpath_exists('//emphasis[@style=\"italic\"]/part-of-speech', XML::XML) = true";
        
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);
        $changed = 0;
        foreach($atomsArray as $atom) {
            $atomModel = Atom::find($atom['id']);
            $xml = $atomModel->xml;
            $brandName = '';

            preg_match('/<emphasis style="italic">([^<]*)<part-of-speech>([^<]*)<\/part-of-speech>([^<]*)<\/emphasis>/i', $xml, $match);
            if ($match){
                $n = $match[2];    //n  n.pl  n.pr   nnbrand
                $brandName = $match[3];   //brand name
                $replaceString = '<part-of-speech>'.$n.'</part-of-speech><emphasis style="italic">'.$brandName.':</emphasis>';
                $newXml = preg_replace('/<emphasis style="italic">([^<]*)<part-of-speech>([^<]*)<\/part-of-speech>([^<]*)<\/emphasis>/i', $replaceString, $xml);
                if ($newXml != $xml){
                    $timestamp = (new Atom())->freshTimestampString();
                    $newAtom = $atomModel->replicate();
                    $newAtom->xml = $newXml;
                    $newAtom->modified_by = null;
                    $newAtom->created_at = $timestamp;
                    $newAtom->updated_at = $timestamp;
                    $changed++;
                    $newAtom->save();
                    echo "$newAtom->entity_id\t$newAtom->alpha_title\n";
                }else{
                    echo "$atomModel->entity_id\t$atomModel->alpha_title\tsame xml\n"; //when preg_replace was not working
                }
            }
            else{
                  echo "$atomModel->entity_id\t$atomModel->alpha_title\tnot match\n";
            }
        }

        /* output messages */
        echo 'total atoms changed: '.$changed."\n";
    }
}