<?php

namespace App\Providers;

use \Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\ServiceProvider;
use Illuminate\Routing\Router;

use App\User;

class ApiUserProvider extends EloquentUserProvider {
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier) {
        $user = parent::retrieveById($identifier);

        if(!$user || !$user->active) {
            return null;
        }

        $user->joinAll();
        self::_loadACL($user);

        return $user;
    }

    /**
     * Retrieve a user by their unique identifier and "remember me" token.
     *
     * @param  mixed  $identifier
     * @param  string  $token
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByToken($identifier, $token) {
        $user = parent::retrieveByToken($identifier, $token);

        if(!$user || !$user->active) {
            return null;
        }

        self::_loadACL($user);

        return $user;
    }

    /**
     * Retrieve a user by the given credentials.
     *
     * @param  array  $credentials
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveByCredentials(array $credentials) {
        $user = parent::retrieveByCredentials($credentials);

        if(!$user || !$user->active) {
            return null;
        }

        self::_loadACL($user);

        return $user;
    }

    /**
     * Load and add the user's permissions.
     *
     * @param ?object $user
     *
     * @return ?object
     */
    protected static function _loadACL($user) {
        if($user) {
            //autodetect the productId
            $params = \Route::current()->parameters();
            $productId = isset($params['productId']) ? (int)$params['productId'] : null;

            $user->loadACL($productId);
            $user->userProducts;
        }

        return $user;
    }
}