<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Domain;
use App\Product;


/**
 * Expected field headers for content_area.txt:
 *
 */
class ImportContentArea extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:contentArea {productId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import contentArea from data/import/content_area_updated.txt into domains table, e.g. import:contentArea 13';
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

        $filename = base_path() . '/data/import/content_area_updated.txt';
        if(!file_exists($filename)) {
            return;
        }

        $xml = trim(file_get_contents($filename)); 
        $xml = '<?xml version="1.0" encoding="UTF-8"?>'.$xml;
        $xmlObject = simplexml_load_string($xml);
        $contentAreas = $xmlObject->xpath('//sub');
        $sort = 0;
        foreach ($contentAreas as $sub){
            $fullName = '';
            $subArr = json_decode(json_encode($sub), true);
            $subName = $subArr['@attributes']['name']; //Adult Health

            if (sizeof($subArr) > 1){
                foreach ($subArr['subsub'] as $subsub){ //cardiovascular
                    $subsubName = $subsub['@attributes']['name'];
                    if (array_key_exists('subsubsub', $subsub)){ //pharmacology/Cardiovascular has subsubsub
                        foreach ($subsub['subsubsub'] as $subsubsub){
                            $fullName = $subName. ': '. $subsubName. ': '. $subsubsub['@attributes']['name'];
                            $domain['code'] = $fullName;
                            $domain['title'] = $fullName;
                            $domain['sort'] = ++$sort;
                            $this->importDomain($domain, $productId);
                        }
                    }
                    else{
                        $fullName = $subName. ': '. $subsubName;
                        $domain['code'] = $fullName;
                        $domain['title'] = $fullName;
                        $domain['sort'] = ++$sort;
                        $this->importDomain($domain, $productId);
                    }
                }
            }
            else{ //Mental Health doesn't have subsub
                $fullName = $subName;
                $domain['code'] = $fullName;
                $domain['title'] = $fullName;
                $domain['sort'] = ++$sort;
                $this->importDomain($domain, $productId);
            }
        }

        echo "Done\n";
    }

    /**
     * Import a domain.
     *
     * @param array $domain The domain as an associative array; currently: code,title,locked,sort,product_id,contributor_id,editor_id
     */
    public function importDomain($domain, $productId){
        $timestamp = (new Domain())->freshTimestampString();
        $domain['id'] = $domain['sort'] + $productId * 100;
        $domain['created_at'] = $timestamp;
        $domain['updated_at'] = $timestamp;
        $domain['contributor_id'] = '0';
        $domain['editor_id'] = 0;
        $domain['product_id'] = $productId;
        DB::table('domains')->insert($domain);
    }
}
