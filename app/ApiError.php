<?php

namespace App;

use Illuminate\Http\Response;
use App\ApiPayload;

/**
 * Provides a standardized way to create API error responses.
 */
class ApiError {
	/**
	 * Build an error response.
	 *
	 * @param int $code An HTTP error response code
	 * @param string|string[]|null $msg (optional) The message(s) to return
	 *
	 * @return Response
	 */
	public static function buildResponse($code, $msg = null) {
		$payload = new ApiPayload();
		$payload->success = false;
		$payload->errorMsg = $msg ? $msg : Response::$statusTexts[$code];

		return new Response($payload, $code);
	}
}