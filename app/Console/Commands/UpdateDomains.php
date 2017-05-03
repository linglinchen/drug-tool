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
    protected $signature = 'update:domains {productId}, for example: php artisan update:domains 3';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Update reviewer_id, editor_id, contributor_id in domains table based on data/import/domain_update.csv';

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
            $editorUserModel = DB::table('users')->where('firstname', 'Editor')->where('lastname', 'Group')->first();
            $domain = $input['Domain'];
            //get the user id based on email address
            if ($input['Contributor Email'] && strlen($input['Contributor Email'])>0 ){
                $contributorEmail = ltrim($input['Contributor Email']);
                $contributorEmail = rtrim($contributorEmail);
                $userModel = DB::table('users')->where('email', $contributorEmail)->first();

                if (preg_match('/\s+/', $contributorEmail)){ //multiple emails
                //find the editor group id in user table
                    $this->updateDomain($domain, $editorUserModel->id, 'editor_id', $productId);
                    $this->updateDomain($domain, 0, 'contributor_id', $productId);
                }else{ //only one email
                    if (in_array($contributorEmail, ['v.studdert@unimelb.edu.au', 'ccg@pullman.com', 'hkw@unimelb.edu.au']) || $input['Contributors'] == 'none'){
                        // if they are editor, populate editor_id, contributor_id will be 0
                        $this->updateDomain($domain, $userModel->id, 'editor_id', $productId);
                        $this->updateDomain($domain, 0, 'contributor_id', $productId);
                    }else{ //real contributor
                        $this->updateDomain($domain, $userModel->id, 'contributor_id', $productId);
                        $this->updateDomain($domain, $editorUserModel->id, 'editor_id', $productId);
                    }
                }
            }else{
                $this->updateDomain($domain, $editorUserModel->id, 'editor_id', $productId);
                $this->updateDomain($domain, 0, 'contributor_id', $productId);
            }

            //update reviewer_id
            if ($input['Reviewer Email'] && strlen($input['Reviewer Email'])>0 ){
                $reviewerEmail = ltrim($input['Reviewer Email']);
                $reviewerEmail = rtrim($reviewerEmail);
                $userModel = DB::table('users')->where('email', $reviewerEmail)->first();

                $this->updateDomain($domain, $userModel->id, 'reviewer_id', $productId);
                
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
        
        DB::table('domains')->where('code', $domain)->where('product_id',$productId)->update([$columnName => $userId]);
    }
}