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
			if($row['parentId'] === $parentId) {
				unset($input[$key]);
				$row['children'] = self::toTree($input, $row['id']);
				$output[] = $row;
			}
		}

		return $output;
	}
}