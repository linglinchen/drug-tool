<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use DB;

use App\User;
use App\Product;

/**
 * Expected field headers for domain_update.csv:
 *
 * Domain,Definition,Contributors,Contributor Email
 */
class UpdateDomains extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'update:domains {productId} {columnName}, for example: php artisan update:domains 3 contributor_id';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update domains based on data/import/domain_update.csv';

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

        $columnName = $this->argument('columnName');
        if(!$columnName) {
            throw new \Exception('Invalid column name.');
        }
        $this->columnName = $columnName;

        $filename = base_path() . '/data/import/domain_update.csv';
        if(!file_exists($filename)) {
            return;
        }

        $lines = preg_split('/\v+/', trim(file_get_contents($filename)));

        //parse the lines as csv
        foreach($lines as $key => $line) {
            $lines[$key] = str_getcsv($line);
        }

        $headers = array_shift($lines);     //first row is expected to contain the headers

        foreach($lines as $line) {
            $domain = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            //get the user id based on email address
            if ($domain['Contributor Email'] && strlen($domain['Contributor Email'])>0 ){
                $contributorEmail = ltrim($domain['Contributor Email']);
                $contributorEmail = rtrim($contributorEmail);

                if (preg_match('/\s+/', $contributorEmail)){ //multiple emails

                }else{
                    $userModel = DB::table('users')->where('email', $contributorEmail)->first();
                    if (in_array($contributorEmail, ['v.studdert@unimelb.edu.au', 'ccg@pullman.com', 'hkw@unimelb.edu.au'])){
                        // if they are editor

                    }else{ //real contributor
                        $this->updateDomain($domain, $userModel->id, $columnName, $productId);
                    }
                }
            }
        }

        echo "Done\n";
    }

    /**
     * update a domain
     *
     * @param array $domain The domain as an associative array
     * @param int $userId The user_id of the contributor in domain_update.csv
     * @param string $columnName Name of the column in domains table that is being updated
     * @param int $productId The product ID
     */
    public function updateDomain($domain, $userId, $columnName, $productId) {
        
        $domainModel = DB::table('domains')
        ->where('code', $domain['Domain'])
        ->where('product_id', $productId)
        ->first();
        $columnNameValue = $domainModel->$columnName;
        if ($columnNameValue != $userId){
            DB::table('domains')->where('code', $domain['Domain'])->where('product_id',$productId)->update([$columnName => $userId]);
        }
    }
}