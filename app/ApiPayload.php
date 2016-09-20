<?php

namespace App;

/**
 * A convenient, standardized way to build JSON API responses.
 */
class ApiPayload extends AppModel {
	public $success = true;

	/**
	 * @param mixed $data (optional) Initialize the model with this data in the payload
	 */
	public function __construct($data = null) {
		if($data !== null) {
			$this->setPayload($data);
		}
	}

	/**
	 * Set the payload, and convert keys to camelCase.
	 *
	 * @param mixed $data Place this data in the payload
	 */
	public function setPayload($data) {
		$data = self::_forceToArray($data);
		$this->payload = self::arrayKeysToCamelCase($data);
	}

	/**
	 * Convert model(s) into arrays.
	 *
	 * @param mixed $data
	 *
	 * @return mixed
	 */
	protected static function _forceToArray($data) {
		if(is_array($data)) {
			foreach($data as $key => $value) {
				$data[$key] = self::_forceToArray($value);
			}
		}
		else if(is_object($data)) {
			$data = method_exists($data, 'toArray') ? $data->toArray() : (array)$data;
			$data = self::_forceToArray($data);
		}

		return $data;
	}
}