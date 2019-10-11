<?php

/* Remove extra tailing spaces inside <usage> for Nursing

    Remove <emphasis> inside <usage>
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;
use App\Product;

class QuickFixCleanUpUsage extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:cleanupusage {productId}'; //php artisan quickfix:cleanupusage 8

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command remove trailing space and <emphasis> tag in <usage> tag.';

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
        self::_cleanUpUsage($productId);
    }

    public static function _cleanUpUsage($productId) {
        ini_set('memory_limit', '1280M');
        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::legacyBuildLatestIDQuery(null, $q);
                })->where('product_id','=', $productId)
                  ->where(function($q){
                    $q->where('xml', 'LIKE', '%(Obsolete)%')
                        ->orwhere('xml', 'LIKE', '%(Slang)%')
                        ->orwhere('xml', 'LIKE', '%(Informal)%')
                        ->orwhere('xml', 'LIKE', '%(Nontechnical)%');
                  })->get();

        foreach($atoms as $atom) {
            $alphaTitle = $atom->alpha_title;

            $xml = $atom->xml;
            /* <usage id="3">
                <emphasis style="italic">(Obsolete) </emphasis>
                </usage>
            */
            preg_match('/(<usage[^>]*>)\s*(\r\n|\n|\r)\s*<emphasis style="italic">(\((\w)+\))\s*<\/emphasis>\s*(\r\n|\n|\r)\s*<\/usage>/',
                $xml, $match);
            if (isset($match) && isset($match[0]) && isset($match[3])){
                //$match[0] is the whole string
                //$match[1] is <usage id="3">
                //$match[3] is (Obsolete)
                $newXml = preg_replace('/(<usage[^>]*>)\s*(\r\n|\n|\r)\s*<emphasis style="italic">(\((\w)+\))\s*<\/emphasis>\s*(\r\n|\n|\r)\s*<\/usage>/',
                    $match[1].$match[3].'</usage> ', $xml);
            }

            if($newXml !== $atom->xml) {
                $newAtom = $atom->replicate();
                $newAtom->xml = $newXml;
                $newAtom->modified_by = null;
              
                $newAtom->save();
            }
        }
    }
}