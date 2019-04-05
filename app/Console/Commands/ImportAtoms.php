<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\Atom;
use App\Molecule;
use App\Product;
use App\Status;
use App\User;

/**
 * Imports atoms from XML file(s) in the data/import/atoms directory. Applies tallman tags to text when tallmanSet is specified to a predefined set.
 * When editing tallman.txt, be sure that you capitalize ONLY the characters that you want tagged as tallman.
 * To avoid headaches, run this after creating the molecules.
 *
 * Reimports using existing entityIDs.
 */
class ImportAtoms extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'import:atoms
						{productId : The ID of product for the imported atoms}
						{statusId : The ID of the status to set on each imported atom [ ID from db | 0-prefixed ID pattern for lookup ]}
						{tallmanSet=false : the name of a preconfigured tallman set [ false | NDR ]}
						{edition=false : the edition number to write out on imported atoms}
						{modifiedBy=false : the author ID to blame for modifications to imported atoms}';

	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import atoms from XML file(s) in the data/import/atoms directory, and place them into the specified product. Do not run without first importing or adding molecules.';

	/**
	 * The contents of the molecules table
	 *
	 * @var array
	 */
	protected $moleculeLookups;

	/**
	 * A translation table for tallman drug names
	 *
	 * @var string[]
	 */
	protected $tallman = [];

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
		$doctype = Product::find($this->productId)->getDoctype();

		$statusId = (int)$this->argument('statusId');
		$statusPattern = (string)$this->argument('statusId');
		//prefixing with '0' will fetch statuses based on the naming convention pattern established on product 1
		if(substr($statusPattern, 0, 1) == '0') {
			$statusCount = true;
			switch ($statusPattern) {
				case '0100':
					$statusId = Status::getDevStatusId($this->productId)->id;
					break;
				case '0200':
					$statusId = Status::getReadyForPublicationStatusId($this->productId)->id;
					break;
				case '0300':
					$statusId = Status::getDeactivatedStatusId($this->productId)->id;
					break;
				default:
					$statusCount = false;
			}

		//explicitly request a status ID
		} else {
			$statusCount = Status::allForProduct($this->productId)
					->where('id', '=', $statusId)
					->count();
		}
		if(!$statusId || !$statusCount) {
			throw new \Exception('Invalid status ID.');
		}
		$this->statusId = $statusId;

		$tallmanSet = (string)$this->argument('tallmanSet');
		//$tallmanSet value is validated in _loadTallman
		$this->tallmanSet = $tallmanSet;

		$edition = (string)$this->argument('edition');
		if($edition!=='false' && !preg_match('/^[0-9]+(\.?[0-9]+)?$/', $edition)) {
			throw new \Exception('Invalid edition value.');
		}
		$this->edition = $edition;

		$modifiedBy = (string)$this->argument('modifiedBy');
		$modifiedByCount = 0;
		if($modifiedBy!=='false' && preg_match('/[^0-9]/', $modifiedBy)) {
			throw new \Exception('Invalid modified by value.');

		} else if($modifiedBy!=='false') {
			$modifiedByCount = User::allForProduct($this->productId)
					->where('user_id', '=', $modifiedBy)
					->count();
		}
		if($modifiedBy!=='false' && !$modifiedByCount) {
			throw new \Exception('Requested modified by ID is not available for this product.');
		}
		$this->modifiedBy = $modifiedBy;

		$this->moleculeLookups = Molecule::getLookups($productId);

		$dataPath = base_path() . '/data/import/atoms/';
		$files = scandir($dataPath);
		$files = array_slice($files, 2);
		foreach($files as $file) {
			if(!preg_match('/\.xml$/i', $file)) {
				continue;       //skip non-xml file
			}

			echo 'Loading ', $file, "\n";

			$xml = file_get_contents($dataPath . $file);
			$xml = $this->_addTallman($xml);

			$chapters = $doctype->extractAtomXML($xml);
			if($chapters) {
				foreach($chapters as $moleculeCode => $atoms) {
					$atomCount = $this->_importAtoms($atoms, $productId, $moleculeCode);
					echo "\t", $moleculeCode, ' - ', $atomCount, ' atom' . ($atomCount != 1 ? 's' : '') . "\n";
				}
			}
			else {
				$atomCount = $this->_importAtoms($atoms, $productId);
				echo "\t<no molecule detected> ", $atomCount, "\n";
			}

			echo "\n";
		}

		echo "Done\n";
	}

	/**
	 * Import an array of atom XML strings. Usually a whole letter node.
	 *
	 * @param string[] $atoms The XML strings to import
	 * @param string|null $moleculeCode (optional) The code of the molecule that these atom belong to
	 *
	 * @return int The number of atoms imported
	 */
	public function _importAtoms($atoms, $productId, $moleculeCode = null) {
		$atom = new Atom();
		$doctype = Product::find($this->productId)->getDoctype();
		$sort = 0;
		foreach($atoms as $atomString) {
			$category = null;
			if ($productId == 7 || $productId == 11){
				preg_match('/<content_area>\s*(\r\n|\n|\r)\s*<entry>(.*)<\/entry>\s*(\r\n|\n|\r)\s*<\/content_area>/Si', $atomString, $matches);
				if ($matches){
					$category = $matches[2];
				}
			}
			else{
				preg_match('/<category[^>]*>(.*)<\/category>/Si', $atomString, $matches);
				if ($matches){
					$category = $matches[1];
				}
			}

			$title = $doctype->detectTitle($atomString);
			$alphaTitle = Atom::makeAlphaTitle($title);
			$timestamp = $atom->freshTimestampString();
			$entityId = $doctype->detectAtomIDFromXML($atomString) ?: Atom::makeUID();
			$atomString = $doctype->assignXMLIds($atomString, $entityId);
			$edition = ($this->edition=='false' ? null : $this->edition);
			$modifiedBy = ($this->modifiedBy=='false' ? null : $this->modifiedBy);
			$sort++;

			$atomData = [
				'entity_id' => $entityId,
				'title' => $title,
				'alpha_title' => $alphaTitle,
				'molecule_code' => $moleculeCode,
				'xml' => $atomString,
				'modified_by' => $modifiedBy,
				'created_at' => $timestamp,
				'updated_at' => $timestamp,
				'status_id' => $this->statusId,
				'product_id' => $this->productId,
				'edition' => $edition,
				'sort' => $sort,
				'domain_code' => $category,
			];

			DB::table('atoms')->insert($atomData);
		}

		return sizeof($atoms);
	}

	/**
	 * Add tallman tags to XML.
	 *
	 * @param string $xml The XML string to modify
	 *
	 * @return string The modified XML string
	 */
	protected function _addTallman($xml) {
		//tallman isn't used on every import
		if($this->tallmanSet === 'false') {
			return $xml;
		}


		$this->_loadTallman();

		foreach($this->tallman as $find => $replacement) {
			$xml = preg_replace($find, $replacement, $xml);
		}

		return $xml;
	}

	/**
	 * Loads the tallman data if needed, and computes searches / replacements.
	 */
	protected function _loadTallman() {
		if(!$this->tallman) {
			//list of predefined tallman sets selected with tallmanSet
			$tallmanSets = [
				'NDR' => 'tallman.txt',
			];

			if(!array_key_exists($this->tallmanSet, $tallmanSets)) {
				throw new \Exception('Invalid tallmanSet value: ' . $this->tallmanSet);
			}

			$dataPath = base_path() . '/data/' . $tallmanSets[$this->tallmanSet];
			$tallman = file_get_contents($dataPath);
			if($tallman === false) {
				throw new \Exception('tallmanSet file was not found: ' . $dataPath);
			}

			$tallman = preg_split('/\v+/S', trim($tallman));
			foreach($tallman as $name) {
				$name = trim($name);

				if(!$name) {
					continue;       //skip blank lines
				}

				//build inner portion of the search regex
				$find = preg_replace('/[A-Z]+/S', '($0)', $name);
				$find = preg_replace('/[a-z]+/S', '($0)', $find);

				//build the replacement
				$i = 0;
				$parts = explode(')(', $find);
				$replacement = '';
				foreach($parts as $part) {
					if(strtolower($part) == $part) {
						$replacement .= '$' . ++$i;     //lowercase
					}
					else {
						$replacement .= '<emphasis style="tallman">$' . ++$i . '</emphasis>';     //uppercase
					}
				}

				//finish building the search regex
				$find = '#\b' . $find . '\b#Si';

				$this->tallman[$find] = $replacement;
			}
		}
	}
}