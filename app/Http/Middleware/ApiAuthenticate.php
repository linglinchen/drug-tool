<?php

namespace App;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;
use Illuminate\Http\Response;

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
            return response('Invalid credentials.', Response::HTTP_UNAUTHORIZED);
        }
        
        if(!self::_productIsAccessible()) {
            return response('You do not have access to this product.', Response::HTTP_FORBIDDEN);
        }

        return $next($request);
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
