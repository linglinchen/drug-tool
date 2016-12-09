<?php

namespace App;

use DB;

use App\AppModel;

/**
 * Manages the ACL structure.
 */
class AccessControlStructure extends AppModel {
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
     * @return Collection The ACL structure
     */
	public function getStructure() {
		$output = array();
		$structure = self::whereRaw('1 = 1')
				->orderBy('parent_id')
				->get();
		foreach($structure as $element) {
			$element->permitted = false;
			$output[$element->id] = $element;
		}

		return collect($output);
	}
}