<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\Domain;


/**
 * Expected field headers for content_area.txt:
 *
 */
class ImportHealthCodes extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'import:healthcodes';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Import contentArea from data/import/content_area.txt into domains table';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $filename = base_path() . '/data/import/health_codes.txt';
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
                            $this->importDomain($domain);
                        }
                    }
                    else{
                        $fullName = $subName. ': '. $subsubName;
                        $domain['code'] = $fullName;
                        $domain['title'] = $fullName;
                        $domain['sort'] = ++$sort;
                        $this->importDomain($domain);
                    }
                }
            }
            else{ //Mental Health doesn't have subsub
                $fullName = $subName;
                $domain['code'] = $fullName;
                $domain['title'] = $fullName;
                $domain['sort'] = ++$sort;
                $this->importDomain($domain);
            }
        }

        echo "Done\n";
    }

    /**
     * Import a domain.
     *
     * @param array $domain The domain as an associative array; currently: code,title,locked,sort,product_id,contributor_id,editor_id
     */
    public function importDomain($domain){
        $name = preg_replace('/\'/', "\'", $domain['code']);
        echo "'".$name."',"."\n";
        $timestamp = (new Domain())->freshTimestampString();
        $domain['id'] = $domain['sort'] + 1000;
        $domain['created_at'] = $timestamp;
        $domain['updated_at'] = $timestamp;
        $domain['product_id'] = 11;
    }
}
