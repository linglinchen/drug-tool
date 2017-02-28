<?php

/* 
    set the sort order of monographs as they were originally imported
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;
use App\Product;

class ExtractRarelyUsed extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'extract:rarely_used {productId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command extracts all rarely used monograph names';

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
        
        self::_extractRarelyUsed($productId);
    }

    /**
     * Extract the rarely used monograph names
     *
     * @param integer $productId The product we are targeting
     *
     * @return void;
     */
    protected static function _extractRarelyUsed($productId) {
        $sql = "select id, alpha_title, xml from atoms 
            where id IN (" . Atom::buildLatestIDQuery()->toSql() . ")
            AND cast (xpath('//monograph[@ru=\"yes\"]', xml::xml) as text[])  != '{}' order by alpha_title";

        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);
        $rarelyUsed = 0;
        foreach($atomsArray as $atom) {
            $xml = $atom['xml'];
            $atomModel = Atom::find($atom['id']);
            $rarelyUsed++;
            echo $atomModel->alpha_title . "\n";
        }

        /* output messages */
        
        echo "\n\n" . 'Rarely used atoms: ' . $rarelyUsed . "\n";
    }
}