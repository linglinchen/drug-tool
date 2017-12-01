<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Atom;
use App\Product;

/**
 * remove the special character (line separator: U+2028) from latest atoms which was introduced by TNQ
 */
class QuickFixRemoveLineSeparator extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:lineseparator {productId}, for example: php artisan quickfix:lineseparator 1';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'remove the special character (line separator: U+2028) from latest atoms xml';

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
        $this->productId = $productId;

        ini_set('memory_limit', '1280M');
        
        $atoms = Atom::allForProduct($productId)
                ->whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->get();

        $count = 0;
        foreach($atoms as $atom) {
            $xml = $atom->xml;
            preg_match('/ /', $xml, $matches);
            if ($matches){
                $count++;
                print_r($xml);
                echo "\n";
            }
        }
    }
}