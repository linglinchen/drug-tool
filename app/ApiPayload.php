<?php

namespace App;

/**
 * A convenient, standardized way to build JSON API responses.
 */
class ApiPayload {
	public $success = true;

	/**
	 * @param mixed $data (optional) Initialize the model with this data in the payload
	 */
	public function __construct($data = null) {
		if($data !== null) {
			$this->payload = $data;
		}
	}

	/**
	 * Return a JSON representation of this model whenever it is cast to a string.
	 */
	public function __toString() {
		return json_encode($this);
	}
}