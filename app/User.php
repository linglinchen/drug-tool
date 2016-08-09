<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

class User extends Authenticatable
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name', 'email', 'password',
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * Returns a list of all users with sensitive fields excluded.
     *
     * @return array The list of users
     */
    public static function publicList() {
        $users = self::all();

        foreach($users as &$user) {
            unset($user['password'], $user['remember_token']);
        }

        return $users;
    }
}
