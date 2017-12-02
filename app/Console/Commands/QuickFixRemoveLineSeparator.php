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

	$atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->where('product_id','=', $this->productId)->get();

        $count = 0;
        foreach($atoms as $atom) {
	    preg_match('/ /', $atom->xml, $matches);
            if ($matches){
                $count++;
                $newAtom = $atom->replicate();
                preg_match('/[\w]�[\w]/', $atom->xml, $matches2); //need to keep a space there
                if ($matches2){
                        echo $atom->id."\t".$atom->entity_id."\t".$atom->alpha_title."replace by space\n";
			            $newAtom->xml = str_replace("\xe2\x80\xa8",'\\u2028', $atom->xml);
                        $newAtom->xml = str_replace('\\u2028',' ', $newAtom->xml);
                }else{
                        echo $atom->id."\t".$atom->entity_id."\t".$atom->alpha_title."\tremoved\n";
                        $newAtom->xml = str_replace("\xe2\x80\xa8",'\\u2028', $atom->xml);
                        $newAtom->xml = str_replace('\\u2028','', $newAtom->xml);
                }
                $newAtom->save();
		        //exit;
	    }
	}
	echo 'Total changed atoms: '.$count."\n";
    }
}
