<?php

namespace App\Http\Requests;

use App\AppModel;

class Request extends \Illuminate\Http\Request {
	/**
	 * Wrap the FormRequest, and convert the array keys from camelCase to snake_case.
	 */
	public function all() {
		return AppModel::arrayKeysToSnakeCase(parent::all());
	}

	/**
	 * Wrap the FormRequest, and convert the array keys from camelCase to snake_case.
	 * Accepts camelCase keys.
	 *
	 * @param  string  $key
	 * @param  string|array|null  $default
	 *
	 * @return string|array
	 */
	public function input($key = null, $default = null) {
		if($key === null) {
			$key = null;
		}
		else if(!parent::input($key)) {
			$key = snake_case($key);
		}

		return AppModel::arrayKeysToSnakeCase(parent::input($key, $default));
	}
}
