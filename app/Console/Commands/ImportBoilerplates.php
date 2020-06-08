<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use DB;

use App\Atom;
use App\Product;
use App\Status;

/**
 * Imports boilerplates from XML file(s) in the data/import/boilerplates directory. Boilerplates must be valid XML following the product's DTD and are handled like atoms but in the boilerplates table
 * To avoid headaches, run this after creating the products.
 */
class ImportBoilerplates extends Command
{
	/**
	 * The name and signature of the console command.
	 *
	 * @var string
	 */
	protected $signature = 'import:boilerplates
						{productId : The ID of product for the boilerplate}
						{boilerTitles* : An array of titles to use for each boilerplate XML file, in file alpha order}';


	/**
	 * The console command description.
	 *
	 * @var string
	 */
	protected $description = 'Import boilerplates from XML file(s) in the data/import/boilerplates directory, and assign them to a single product using an array for boilerplate titles. Do not run without first importing or adding products.';

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
		$doctype = Product::find($productId)->getDoctype();

		$boilerTitles = (array)$this->argument('boilerTitles');
		if(!$boilerTitles) {
			throw new \Exception('Invalid boilerplate title.');
		}
		$this->boilerTitles = $boilerTitles;

		$dataPath = base_path() . '/data/import/boilerplates/';
		$files = scandir($dataPath);
		$files = array_slice($files, 2);
		foreach($files as $file) {
			if(!preg_match('/\.xml$/i', $file)) {
				continue;       //skip non-xml file
			}

			echo 'Loading ', $file, "\n";

			$boilerTitle = current($boilerTitles);

			$xml = file_get_contents($dataPath . $file);

			$boilerplates = $doctype->extractAtomXML($xml);
			if($boilerplates) {
				$boilerCount = $this->_importBoilerplates($boilerplates, current($this->boilerTitles));
				echo "\t", current($this->boilerTitles), ' - ', $boilerCount, ' boilerplate' . ($boilerCount != 1 ? 's' : '') . "\n";
			} else {
				echo "\t<no boilerplates detected> ", "\n";
			}

			echo "\n";

			next($this->boilerTitles);
		}

		echo "Done\n";
	}

	/**
	 * Import an array of boilerplate XML strings. Usually just one per.
	 *
	 * @param string[] $boilerplates The XML strings to import
	 * @param string|null $boilerTitle (optional) The title to assign to this boilerplate
	 *
	 * @return int The number of boilerplates imported
	 */
	public function _importBoilerplates($boilerplates, $boilerTitle = null) {
		$boilerplate = new Atom();
		$doctype = Product::find($this->productId)->getDoctype();
		$sort = 0;
		foreach($boilerplates['boilerplate'] as $boilerplateString) {

			$boilerplateData = [
				'title' => $boilerTitle,
				'xml' => $boilerplateString,
				'product_id' => $this->productId,
			];

			DB::table('boilerplates')->insert($boilerplateData);
		}

		return sizeof($boilerplates);
	}

}
