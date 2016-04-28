<?php

namespace App;

use Illuminate\Http\Response;
use App\ApiPayload;

class ApiError {
	public static function buildResponse($code, $msg = null) {
		$payload = new ApiPayload();
		$payload->success = false;
		$payload->errorMsg = $msg ? $msg : Response::statusTexts[$code];

		return new Response($payload, $code);
	}
}