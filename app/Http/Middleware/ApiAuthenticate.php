<?php

namespace App;

use Closure;
use Illuminate\Contracts\Auth\Factory as AuthFactory;

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
        $authorized = $this->auth->guard($guard)->basic() ?: $next($request);

        return $this->auth->guard($guard)->basic() ?: $next($request);
    }
}
