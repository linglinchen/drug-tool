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
		if(is_object($data) && method_exists($data, 'toArray')) {
			$data = $data->toArray();
		}

		$this->payload = self::arrayKeysToCamelCase($data);
	}
}