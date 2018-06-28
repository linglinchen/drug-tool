<?php
namespace App\Http\Controllers;

use Storage;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Requests\Request;
use Illuminate\Http\Response;



class S3ImageController extends Controller
{



public function testMeAction($productId){

	\Storage::disk('s3')->makeDirectory('1');


}
//$exists = Storage::disk('s3')->exists('file.jpg');
/*    	return view('image-upload');*/
//return $exists;

    public function imageUploadAction($productId, Request $request)
    {


    	\Storage::disk('s3')->makeDirectory('1');


$s3 = App::make('aws')->createClient('s3');
return('Howdy from bucket');

$exists = file_get_contents("http://metis-imageserver-dev.elseviermultimedia.us/file.jpg");
print_r($exists);
if($exists){

print_r('yes sir' .$exists);
} else{
    print_r('nope');
}
// Call listBuckets with no parameters
$buckets = $s3->listBuckets();

print_r($buckets);

/*    	$this->validate($request, [

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);*/

$this->validate($request, ['image' => 'required|image']);

        $imageName = time().'.'.$request->image->getClientOriginalExtension();

        $image = $request->file('image');

        $t = Storage::disk('s3')->put($imageName, file_get_contents($image), 'public');

        $imageName = Storage::disk('s3')->url($imageName);

    	return( $imageName);

/*
    	return back()

    		->with('success','Image Uploaded successfully.')

    		->with('path',$imageName);*/




//         $this->validate($request, ['image' => 'required|image']);
//         if($request->hasfile('image'))
//          {
//             $file = $request->file('image');
//             $name=time().$file->getClientOriginalName();
//             $filePath = 'images/' . $name;
//             Storage::disk('s3')->put($filePath, file_get_contents($file));
//             return back()->with('success','Image Uploaded successfully');
//     }
 }


    /**

    * Manage Post Request

    *

    * @return void

    */

/*    public function imageUploadPost(Request $request)

    {

    	$this->validate($request, [

            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

        ]);




        $imageName = time().'.'.$request->image->getClientOriginalExtension();

        $image = $request->file('image');

        $t = Storage::disk('s3')->put($imageName, file_get_contents($image), 'public');

        $imageName = Storage::disk('s3')->url($imageName);




    	return back()

    		->with('success','Image Uploaded successfully.')

    		->with('path',$imageName);

    }*/


}