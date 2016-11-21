<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppModel extends Model {
	/**
	 * Get a list of this model's table columns.
	 *
	 * @return string[]
	 */
	public function getMyColumns() {
		$table = $this->table;
		$columns = \Schema::getColumnListing($table);

		$mapping = function ($column) use ($table) {
			return $table . '.' . $column;
		};

		return array_map($mapping, $columns);
	}

	/**
	 * Convert a flat array into a tree.
	 *
	 * @param mixed[] $input The flat array
	 * @param ?integer $parentId The parent node's ID
	 *
	 * @return mixed[] The tree
	 */
	public static function toTree(&$input, $parentId = null) {
		$output = [];

		foreach($input as $key => $row) {
			if($row['parent_id'] === $parentId) {
				unset($input[$key]);
				$row['children'] = self::toTree($input, $row['id']);
				$output[] = $row;
			}
		}

		return $output;
	}

	/**
	 * Recursively convert an array's keys to camelCase.
	 *
	 * @param mixed[] $input The array to transform
	 *
	 * @param mixed[] The transformed array
	 */
	public static function arrayKeysToCamelCase($input) {
		if(!is_array($input)) {
			return $input;
		}

		$output = [];

		foreach($input as $key => $value) {
			$output[camel_case($key)] = is_array($value) ? self::arrayKeysToCamelCase($value) : $value;
		}

		return $output;
	}

	/**
	 * Recursively convert an array's keys to snake_case.
	 *
	 * @param mixed[] $input The array to transform
	 *
	 * @param mixed[] The transformed array
	 */
	public static function arrayKeysToSnakeCase($input) {
		if(!is_array($input)) {
			return $input;
		}

		$output = [];

		foreach($input as $key => $value) {
			$output[snake_case($key)] = $value;
		}

		return $output;
	}

	/**
	 * Converts an associative array into a Laravel Response containing a downloadable CSV file.
	 *
	 * @param string $filename The name you'd like to give the file
	 * @param string[] $headings The CSV's column headings
	 * @param array $data The data to convert
	 *
	 * @return Response
	 */
	public static function arrayToCsv($filename, $headings, $data) {
		$headers = [
			'Cache-Control' => 'must-revalidate, post-check=0, pre-check=0',
			'Content-type' => 'text/csv',
			'Content-Disposition' => 'attachment; filename="' . $filename . '"',
			'Expires' => '0',
			'Pragma' => 'public',
			'Access-Control-Expose-Headers' => 'content-type,content-disposition'
		];

		$callback = function () use ($headings, $data) {
			$out = fopen('php://output', 'w');

			$blank = array_fill_keys($headings, null);

			fputcsv($out, $headings);
			foreach($data as $row) {
				$preparedRow = $blank;
				foreach($row as $key => $value) {
					if(array_key_exists($key, $preparedRow)) {
						$preparedRow[$key] = $value;
					}
				}

				fputcsv($out, $preparedRow);
			}

			fclose($out);
		};


		return response()->stream($callback, 200, $headers);
	}

	/**
	 * Get the current productId. This usually comes from the URL parameter.
	 *
	 * @return ?integer
	 */
	public static function getCurrentProductId() {
		return \Auth::user()->ACL->productId;
	}

	/**
	 * Select all that belong to the current product.
	 */
	public static function allForCurrentProduct() {
		$productId = \Auth::user()->ACL->productId;

		return self::where('product_id', '=', $productId);
	}
}