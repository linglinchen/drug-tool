<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;

/**
 * Manages the ACL structure.
 */
class AccessControlStructure extends Model {
	/**
	 * @var string $table This model's corresponding database table
	 */
	protected $table = 'access_control_structure';

	/**
	 * @var string[] $guarded Columns that are protected from writes by other sources
	 */
    protected $guarded = ['id'];

	/**
	 * @var string[] $dates The names of the date columns
	 */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Load and initialize the ACL structure.
     *
     * @return array[] The ACL structure
     */
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