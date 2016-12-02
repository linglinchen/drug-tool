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
        self::_fixOrder();
    }

    /**
     * Fix the sort order of atoms based on their original order in the XML.
     *
     * @return void;
     */
    protected static function _fixOrder() {
        $importOrders = self::_getOrderFromXml();

        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->get();
        $totalChanged = 0;

        foreach($atoms as $atom) {
            $newSort = self::_getNewSort($atom, $importOrders);
            if ($newSort == NULL){ //the atom is not in xml
                 if ($atom->sort !== NULL){
                    $newAtom = $atom->replicate();
                    $newAtom->sort = NULL;
                    $newAtom->modified_by = NULL;
                    $newAtom->save();
                }
            }
            elseif ($newSort != $atom->sort){
                $newAtom = $atom->replicate();
                $newAtom->sort = $newSort;
                $newAtom->modified_by = NULL;
                $newAtom->save();
                $totalChanged++;
            }
        }

        /* output messages */
        echo 'Total atoms: ' . count($atoms) . "\n";
        echo 'Total atoms that changed order: ' . $totalChanged . "\n";
    }

    /**
     * Find the sort order of an atom based on its original position in the XML.
     *
     * @param object $atom The atom we are looking for
     * @param array $importOrders An associative array containing the sort orders of each chapter
     *
     * @return ?integer;
     */
    protected static function _getNewSort($atom, $importOrders) {
        $newSort = $atom->sort;

        //find the corresponding atom in xml 
        foreach ($importOrders as $xmlFile => $atomOrder){
            preg_match('/letter_(.*)_20\d\d\.xml/i', $xmlFile, $chapterMatch);
            preg_match('/(appendix_.*)_20\d\d\.xml/i', $xmlFile, $appendixMatch);
            
            $match = $chapterMatch ?: $appendixMatch;
            if ($match){
                foreach ($atomOrder as $name => $order){
                    $nameFormatted =  Atom::makeAlphaTitle($name);
                    if ($nameFormatted == $atom->alpha_title && $match[1] == $atom->molecule_code){
                        $newSort = $order;
                        //break;
                        return $newSort;
                    }
                }
            }
        }

        return NULL;   //the atom is not in xml
    }

    /**
     * Get the original order of every atom that was in the imported XML.
     *
     * @return array An associative array containing the sort orders of each chapter
     */
    protected static function _getOrderFromXml() {
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
                preg_match('/<group_title>(.*)<\/group_title>/iU', $line, $groupMatch);
                preg_match('/<mono_name>(.*)<\/mono_name>/iU', $line, $monoMatch);
                $match = $groupMatch ?: $monoMatch;
                if ($match){
                    $order++;
                    $atomName = trim($match[1]);
                    $importOrders[$dirFile][$atomName] = $order;
                }
            }
            fclose($fh);
        }

        return $importOrders;
    }
}