<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Response;

use App\ApiError;

class ApiAuthenticate {
    /**
     * The guard factory instance.
     *
     * @var \Illuminate\Contracts\Auth\Factory
     */
    protected $auth;

    /**
     * Create a new middleware instance.
     *
     * @param  \Illuminate\Contracts\Auth\Factory  $auth
     * @return void
     */
    public function __construct(AuthFactory $auth) {
        $this->auth = $auth;
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  $guard
     * @return mixed
     */
    public function handle($request, Closure $next, $guard = null) {
        $unauthorized = $this->auth->guard($guard)->basic();
        if($unauthorized) {
            return ApiError::buildResponse(Response::HTTP_UNAUTHORIZED, 'Invalid credentials.');
        }
        
        if(!self::_productIsAccessible()) {
            return ApiError::buildResponse(Response::HTTP_FORBIDDEN, 'You do not have access to this product.');
        }

        try {
            $result = $next($request);
        }
        catch(\Exception $e) {
            return ApiError::buildResponse(Response::HTTP_INTERNAL_SERVER_ERROR, $e->getMessage());
        }

        return $result;
    }

    /**
     * Check if the user has access to the product if one is being requested.
     *
     * @return boolean
     */
    protected static function _productIsAccessible() {
        $params = \Route::current()->parameters();
        if(isset($params['productId'])) {
            $productId = (int)$params['productId'];
            $accessibleProductIds = \Auth::user()->userProducts->pluck('product_id')->all();
            
            return in_array($productId, $accessibleProductIds);
        }

        return true;
    }
}
