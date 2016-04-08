<?php

namespace App;

class ApiPayload {
	public $success = true;

	public function __construct($data = null) {
		if($data !== null) {
			$this->payload = $data;
		}
	}

	public function __toString() {
		return json_encode($this);
	}
}