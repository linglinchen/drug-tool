<?php

/* 
	set the sort order of monographs as they were originally imported
*/

namespace App\Console\Commands;

use Illuminate\Console\Command;

use App\Atom;

class QuickFixOrder extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:order';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command sets the sort order of monographs as they were originally imported';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        self::fixOrder();
    }

    public static function fixOrder() {
		//get order from xml
		$import_order_arr = [];
		$dir = base_path() . '/data/import/Skidmore_NDR_2017_4_all/';
		//$dir= "/var/www/files/Skidmore_NDR_2017_4_all";
		$dir_arr = scandir($dir);
		foreach ($dir_arr as $dir_file){
			if($dir_file != '.' && $dir_file !='..'){
				$file_name = "$dir/$dir_file";
				$fh = fopen($file_name, 'r');
				$order = 0;
				while(!feof($fh)){
					$line = fgets($fh);
					if (preg_match("/<group_title>(.*)<\/group_title>/i", $line,$match ) || 
					    preg_match("/<mono_name>(.*)<\/mono_name>/i", $line,$match )){
						$order ++;
						$atom_name = trim($match[1]);
						$import_order_arr[$dir_file][$atom_name] = $order;
					}
				}
				fclose($fh);
			}
		}	

		//print_r($import_order_arr['letter_k_2017.xml']); exit;

        $atoms = Atom::whereIn('id', function ($q) {
                    Atom::buildLatestIDQuery(null, $q);
                })->get();
        $total_current = 0;
        $total_imported = 0;
		$count=0;

		
		$chapter_arr = range('a', 'z');
		array_push($chapter_arr, 'appendix_a');
		array_push($chapter_arr, 'appendix_c');
	
		$log_arr = [];
		$testing_dir = base_path() . '/data/testing';
		if (!is_dir($testing_dir)){
			mkdir($testing_dir, 0777, true);
		}
		//$testing_dir = "/var/www/files/testing";
		foreach ($chapter_arr as $chap){
			$new_order_log = fopen("$testing_dir/".$chap."_changed.txt", "w");
				$same_log = fopen("$testing_dir/".$chap."_same.txt", "w");
				$notXml_log = fopen("$testing_dir/".$chap."_notXml.txt", "w");
				$log_arr[$chap] = [];
				$log_arr[$chap]['diff'] = $new_order_log;
				$log_arr[$chap]['same'] = $same_log; 
				$log_arr[$chap]['notXml'] = $notXml_log; 
		}

		$xml_mono_list = [];
        foreach($atoms as $atom) {
			if ( $atom->molecule_code == 'appendix_a' ){
							echo "chapter: \tmc: $atom->molecule_code\r\n";
						}
				
			$count++;
			$total_current++;
			$newAtom = $atom->replicate();
			

			//find the corresponding atom in xml 
			$flag = 0; //no matching xml
			foreach ($import_order_arr as $xml_file => $info){
				
				$chapter ='';
				if (preg_match("/letter_(.*)_2017\.xml/i", $xml_file,$match )){
					$chapter = $match[1];
					
				}elseif (preg_match("/(appendix_.*)_2017\.xml/i", $xml_file,$match)){
					$chapter = $match[1];
					
				}else{
					echo "please check xml file name, $xml_file !\n";
					exit;
				}
				

				foreach ($info as $mono_name => $order){
        				$mono_name_formatted =  Atom::makeAlphaTitle($mono_name);
					if ($mono_name_formatted == $atom->alpha_title && $chapter == $atom->molecule_code){
						$newAtom->sort = $order;
						$total_imported++;
						$flag = 1; //found match in xml
						$xml_mono_list[$chapter][$order] = [$atom->alpha_title];
					
						
						//echo "$chapter $order $mono_name\r\n";  
						break;
					}
					
				}
			}
			
			if ($flag == 1){   //there is match in xml
				if ($newAtom->sort != $atom->sort){
					$newAtom->modified_by = NULL;
					$newAtom->save();
					
					fwrite($log_arr[$atom->molecule_code]['diff'], "new order for atom ".$atom->id.' '.$atom->entity_id.' '.$atom->title." has been updated! $atom->sort => $newAtom->sort\r\n");
				
					
				}else{
					if (strlen($atom->molecule_code) > 0){ //some testing atoms don't have molecule_code
					fwrite($log_arr[$atom->molecule_code]['same'], 'atom '.$atom->id.' '.$atom->entity_id.' '.$atom->alpha_title." order remains the same: $atom->sort\r\n");
					}
				}
			}else{ //no match in xml
				//echo "$atom->id $atom->molecule_code $atom->entity_id $atom->title sort: $atom->sort\r\n";
				if (strlen($atom->molecule_code) > 0){ //some testing atoms don't have molecule_code
					fwrite($log_arr[$atom->molecule_code]['notXml'], 'atom '.$atom->id.' '.$atom->entity_id.' '.$atom->alpha_title." not found in XML: $atom->sort\r\n");
				}

				if (!is_null($atom->sort)){
						$newAtom->sort = NULL;
						$newAtom->modified_by = NULL;
						$newAtom->save();
					
				}
			}
			
        }

		//print out the xml mono list
		
		$xml_mono_list_fh = fopen(base_path() . '/data/xml_mono_list.txt', 'w');
		ksort($xml_mono_list);
		//var_dump($xml_mono_list['appendix_a']); exit;
	
		 foreach ($xml_mono_list as $ch => $values){
			 ksort($values);
		
			foreach ($values as $sort => $alpha_title){
				
		 		$str = "$ch\t$sort\t$alpha_title[0]\r\n";
		 		fwrite($xml_mono_list_fh, $str);
		 	}
		 }
		fclose($xml_mono_list_fh);

		foreach ($log_arr as $ch => $arr){
			foreach ($arr as $diff_same => $filehandle){
				fclose($filehandle);
			}
		}
		
	
        /* output messages */
       
        echo 'Total atoms: ' . count($atoms) . "\n";
        echo 'Total atoms that match xml: ' . $total_imported . "\n";
        

    }
}