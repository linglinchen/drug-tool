<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class AppModel extends Model {
	public function getMyColumns() {
		$table = $this->table;
		$columns = \Schema::getColumnListing($table);

		$mapping = function ($column) use ($table) {
            return $table . '.' . $column;
        };

        return array_map($mapping, $columns);
	}
}