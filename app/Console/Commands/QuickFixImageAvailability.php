<?php

/* run this script to correct the error of availability="undefined" in atoms table, xml column for suggested/implemented images.
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Comment;
use App\Product;
use App\Status;
use App\Molecule;
use App\User;

class QuickFixImageAvailability extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */

    /**
     * The console command description.
     *
     * @var string
     */
    
    protected $signature = 'quickfix:imageAvailability';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'some atoms xml has availability="undefined" in component tag, that is due to a bug in implementing suggested images.  This fix will retrieve the availabilty information from comments table and update atoms xml';

	/**
	 * Execute the console command.
	 *
	 * @return mixed
	 */
	public function handle() {
        $atoms = Atom::where('xml', 'LIKE', '%availability="undefined"%')->get();
        $atomsArray = json_decode(json_encode($atoms), true);
        $changed = 0;
         foreach($atomsArray as $atom) {
            $availabilityInfo = [];
            $comments = Comment::where('atom_entity_id', '=', $atom['entity_id'])->get();
            $commentsArray = json_decode(json_encode($comments), true);
            foreach($commentsArray as $comment){
                $commentModel = Comment::find($comment['id']);
                preg_match('/<\/query>$/', $commentModel->text, $matches);
                if (substr($commentModel->text, 0, 6) == '<query' && $matches){
                    $obj = simplexml_load_string($commentModel->text);
                    $src = '';
                    $availability = '';

                    $availabilityNodes = $obj->xpath('//availability');
                    if ($availabilityNodes){
                        $availabilityNodes = json_encode($availabilityNodes);
                        $availabilityNodes = (array)json_decode($availabilityNodes, true);
                        $availability = $availabilityNodes[0][0];
                    }

                    $fileNodes = $obj->xpath('//file');
                    if ($fileNodes){
                        $fileNodes = json_encode($fileNodes);
                        $fileNodes = (array)json_decode($fileNodes, true);
                        if ($fileNodes[0] && $fileNodes[0]['@attributes'] && $fileNodes[0]['@attributes']['src']){
                            $src = $fileNodes[0]['@attributes']['src'];
                            $src = preg_replace('/\.\w+$/i', '', $src); //get rid of .JPG
                        }
                    }
                    $availabilityInfo[$src]=$availability;
                }
            }

            $atomModel = Atom::find($atom['id']);
            $xml = $atomModel->xml;
            //add this header to xml so later processing won't do unwanted encoding, e. g. change '-' to &#x2014
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'.$xml;
            $xmlObject = simplexml_load_string($xml);
            $figureNodes = $xmlObject->xpath('//component[@type="figure"]');
            if ($figureNodes){
                foreach ($figureNodes as $figureNode){
                    $src = '';
                    $srcSimple = '';
                    $availability = '';
                    if ($figureNode->file && $figureNode->file->attributes() && $figureNode->file->attributes()->src){
                        $src = $figureNode->file->attributes()->src;
                        $srcSimple = preg_replace('/suggested\/\d+\//', '', $src); // change suggested/2018/1531759130097_p6v0RkxH to be 1531759130097_p6v0RkxH
                    }

                    if ($figureNode->attributes() && $figureNode->attributes()->availability){
                        $attributes = $figureNode->attributes();
                        if ($attributes->availability == 'undefined' && isset($availabilityInfo[$srcSimple])){
                            $attributes->availability = $availabilityInfo[$srcSimple];
                            $xmlString = $xmlObject->asXML();
        
                            //remove the header line that's generated by asXML()
                            $newXml = preg_replace('/<\?xml version="1\.0" encoding="UTF-8"\?>\n/', '', $xmlString);

                            if($newXml !== $atomModel->xml) {
                                $atomModel->xml = $newXml;
                                //$newAtom = $atomModel->replicate();
                                //$newAtom->xml = $newXml;
                                //$newAtom->modified_by = null;
                                //$newAtom->save();
                                $atomModel->save();
                                 $changed++;
                                echo 'atom: '.$atom['entity_id'].' '.$atom['alpha_title'].' '.$src."\n";
                            }
                        }
                    }
                }
            }
         }
        /* output messages */
        echo 'total components changed: '.$changed."\n";
    }

}