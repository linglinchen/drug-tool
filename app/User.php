<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;

use App\AccessControl;

class User extends Authenticatable {
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
        'password', 'remember_token', 'ACL'
    ];

    public $ACL;

    /**
     * Set up the Product relationship.
     *
     * @returns HasManyThrough
     */
    public function products() {
        return $this->hasManyThrough('App\Product', 'App\UserProduct');
    }

    /**
     * Set up the UserProduct relationship.
     *
     * @returns HasManyThrough
     */
    public function userProducts() {
        return $this->hasMany('App\UserProduct');
    }

    /**
     * Returns a list of all users with sensitive fields excluded.
     *
     * @return array The list of users, indexed by id
     */
    public static function publicList() {
        $output = [];

        $users = self::all();
        foreach($users as $user) {
            unset($user['password'], $user['remember_token']);
            $output[$user['id']] = $user;
        }

        return $output;
    }

    /**
     * Load this user's ACL.
     *
	 * @param ?integer $productId (optional) The ACL's productId
     */
    public function loadACL($productId = null) {
        $ACL = new AccessControl($productId);
        $ACL->loadPermissions($this->toArray());
        
        $this->ACL = $ACL;
    }

    /**
     * Get this user's explicit permissions for every product they can access.
     *
     * @returns array
     */
    public function getPermissions() {
		$userProducts = $this->userProducts;
        $productIds = $userProducts->pluck('product_id')->all();
        
		return AccessControl::whereIn('product_id', $productIds)
				->where(function ($q) {
                    $groupIds = $this->userProducts->pluck('group_id')->all();
					$q->where('user_id', '=', $this->id)
							->orWhereIn('group_id', $groupIds);
				})
				->get();
    }
}
