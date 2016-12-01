<?php

/* 
    set the sort order of monographs as they were originally imported
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class QuickFixOrder extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command sets the sort order of monographs as they were originally imported';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        self::fixOrder();
    }

    public static function fixOrder() {
        //get order from xml
        $importOrders = [];
        $dir = base_path() . '/data/import/atoms/';
        $dirFiles = scandir($dir);
        array_splice($dirFiles, 0, 2);
        foreach ($dirFiles as $dirFile){
            $fileName = $dir.'/'.$dirFile;
            $fh = fopen($fileName, 'r');
            $order = 0;
            while(!feof($fh)){
                $line = fgets($fh);
                preg_match('/<group_title>(.*)<\/group_title>/iU', $line,$groupMatch);
                preg_match('/<mono_name>(.*)<\/mono_name>/iU', $line,$monoMatch);
                $match = [];

                if ($groupMatch){
                    $match = $groupMatch;
                }elseif ($monoMatch){
                    $match = $monoMatch;
                }
                if ($match){
                    $order ++;
                    $atomName = trim($match[1]);
                    $importOrders[$dirFile][$atomName] = $order;
                }
            }
            fclose($fh);
        }

        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->get();
        $totalImported = 0;

        foreach($atoms as $atom) {
            $newAtom = $atom->replicate();

            //find the corresponding atom in xml 
            $flag = false; //no matching xml
            foreach ($importOrders as $xmlFile => $atomOrder){
                
                preg_match('/letter_(.*)_2017\.xml/i', $xmlFile, $chapterMatch );
                preg_match('/(appendix_.*)_2017\.xml/i', $xmlFile,$appendixMatch);

                $match = [];
                if ($chapterMatch){
                    $match = $chapterMatch;
                }elseif ($appendixMatch){
                    $match = $chapterMatch;
                }
                
                if ($match){
                    foreach ($atomOrder as $name => $order){
                        $nameFormatted =  Atom::makeAlphaTitle($name);
                        if ($nameFormatted == $atom->alpha_title && $match[1] == $atom->molecule_code){
                            $newAtom->sort = $order;
                            $totalImported++;
                            $flag = true; //found match in xml
                            break;
                        }
                    }
                }
            }
            
            if ($flag){   //there is match in xml
                if ($newAtom->sort != $atom->sort){
                    $newAtom->modified_by = NULL;
                    $newAtom->save();
                }
            }else{ //no match in xml

                if (!is_null($atom->sort)){
                        $newAtom->sort = NULL;
                        $newAtom->modified_by = NULL;
                       $newAtom->save();
                }
            }
        }

        /* output messages */
        echo 'Total atoms: ' . count($atoms) . "\n";
        echo 'Total atoms that match xml: ' . $totalImported . "\n";
    }
}