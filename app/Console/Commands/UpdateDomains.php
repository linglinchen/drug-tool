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
            $input = array_combine($headers, $line);     //this gives us an associative array that will be easy to work with
            //get the user id based on email address
            if ($input['Contributor Email'] && strlen($input['Contributor Email'])>0 ){
                $contributorEmail = ltrim($input['Contributor Email']);
                $contributorEmail = rtrim($contributorEmail);
                $domain = input['Domain'];
                $userModel = DB::table('users')->where('email', $contributorEmail)->first();

                if (preg_match('/\s+/', $contributorEmail)){ //multiple emails
                //find the editor group id in user table
                    $editorUserModel = DB::table('users')->where('firstname', 'Editor')->where('lastname', 'Group')->first();
                    $this->updateDomain($domain, $editorUserModel->id, 'editor_id', $productId);
                    $this->updateDomain($domain, 0, 'contributor_id', $productId);
                }else{ //only one email
                    if (in_array($contributorEmail, ['v.studdert@unimelb.edu.au', 'ccg@pullman.com', 'hkw@unimelb.edu.au']) || $contributorEmail == 'none'){
                        // if they are editor, populate editor_id, contributor_id will be 0
                        $this->updateDomain($domain, $userModel->id, 'editor_id', $productId);
                        $this->updateDomain($domain, 0, 'contributor_id', $productId);
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
        ->where('code', $domain)
        ->where('product_id', $productId)
        ->first();
        $columnNameValue = $domainModel->$columnName;
        if ($columnNameValue != $userId){
            DB::table('domains')->where('code', $domain)->where('product_id',$productId)->update([$columnName => $userId]);
        }
    }
}