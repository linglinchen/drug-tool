<?php

namespace App\Providers;

use \Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\ServiceProvider;

use App\UserProduct;

class ApiUserProvider extends EloquentUserProvider {
    /**
     * Retrieve a user by their unique identifier.
     *
     * @param  mixed  $identifier
     * @return \Illuminate\Contracts\Auth\Authenticatable|null
     */
    public function retrieveById($identifier) {
        $user = parent::retrieveById($identifier);

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

        return $user;
    }
}