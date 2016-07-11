<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

class AccessControlStructure extends Model {
	protected $table = 'access_control_structure';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

	public function getStructure() {
		$output = array();
		$structure = self::whereRaw('1 = 1')
				->orderBy('parentId')
				->get();
		foreach($structure as $element) {
			$element['permitted'] = false;
			$output[$element['id']] = $element;
		}

		return $output;
	}
}