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
}