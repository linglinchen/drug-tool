<?php

/**
 * Change section type to be intravenous from none for IV records
 */

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use App\Atom;

class QuickFixContentArea extends Command {
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'quickfix:contentArea';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'This command changes content area, integrated process and priority concetps for nclex question xml.';

    /**
     * Execute the console command.
     *
     * @return mixed
     */
    public function handle() {
        $sql = "select id, alpha_title, xml, domain_code from atoms 
            where id IN 
                (SELECT MAX(id) as id
                    FROM atoms
                    WHERE (
		                deleted_at IS NULL
		                AND product_id = 7
		            )
                    GROUP BY entity_id
                )
            AND product_id=7";
        
        $atoms = DB::select($sql);
        $atomsArray = json_decode(json_encode($atoms), true);
        $changed = 0;
        foreach($atomsArray as $atom) {
            $flag = 0;
            $xml = $atom['xml'];
            $domainOri = $atom['domain_code'];
            $domain = $domainOri;

            switch ($domain){
                case 'Child Health':
                    $domain = 'Pediatrics';
                    $flag = 1;
                    break;
                case 'Fundamental Skills':
                    $domain = 'Foundations of Care';
                    $flag = 1;
                    break;
                case 'Critical Care':
                    $domain = 'Complex Care';
                    $flag = 1;
                    break;
                default:
            }

            $atomModel = Atom::find($atom['id']);
             //if ($atomModel->entity_id == '5b22b748c6b0a153399278'){ //2 content_area and priorityConcepts
                //if ($atomModel->entity_id == '5b22b748d5c5e760402499'){ //35
                //if ($atomModel->entity_id == '5b22b74a24556383434119'){ //746 integratedProcess
                //if ($atomModel->entity_id == '5b22b74ae3f47955556941'){ //1202 negative control
            //add this header to xml so later processing won't do unwanted encoding, e. g. change '-' to &#x2014
            $xml = '<?xml version="1.0" encoding="UTF-8"?>'.$xml;
            $xmlObject = simplexml_load_string($xml);

            //get the content area
            $contentAreas = $xmlObject->xpath('//content_area');
            foreach ($contentAreas as $contentArea){
                switch ($contentArea->entry){
                    case 'Child Health':
                        $contentArea->entry = 'Pediatrics';
                        $flag = 1;
                        break;
                    case 'Fundamental Skills':
                        $contentArea->entry = 'Foundations of Care';
                        $flag = 1;
                        break;
                    case 'Critical Care':
                        $contentArea->entry = 'Complex Care';
                        $flag = 1;
                        break;
                    default:
                }
            }

            //get the integrated Process
            $integratedProcesses = $xmlObject->xpath('//integrated_process');
            foreach ($integratedProcesses as $integratedProcess){
                if ($integratedProcess->entry == 'Culture and Spirituality'){
                        $integratedProcess->entry = 'Culture, Spirituality, Ethnicity';
                        $flag = 1;
                }
            }

            //get the priority concept
            $priorityConcepts = $xmlObject->xpath('//priority_concepts');
            foreach ($priorityConcepts as $priorityConcept){
                for ($i=0; $i<=count($priorityConcept->entry); $i++){
                    if (preg_match('/Fluid and Electrolyte Balance/i', $priorityConcept->entry[$i], $match)){
                        $priorityConcept->entry[$i] = preg_replace('/Fluid and Electrolyte Balance/i', 'Fluids and Electrolytes', $priorityConcept->entry[$i]);
                        $flag = 1;
                    }
                }
            }

            $xmlString = $xmlObject->asXML();

            //remove the header line that's generated by asXML()
            $newXml = preg_replace('/<\?xml version="1\.0" encoding="UTF-8"\?>\n/', '', $xmlString);
            $timestamp = (new Atom())->freshTimestampString();
            if($flag == 1) {
                $newAtom = $atomModel->replicate();
                $newAtom->xml = $newXml;
                $newAtom->domain_code = $domain;
                $newAtom->modified_by = null;
                $newAtom->created_at = $timestamp;
                $newAtom->updated_at = $timestamp;
                $changed++;
                //$newAtom->save();
                echo "$newAtom->entity_id\t$newAtom->alpha_title\n";
            }
            //}
        }

        /* output messages */
        echo 'total atoms changed: '.$changed."\n";
    }
}