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
     * Get a user that has access to the specified product.
     *
     * @param integer $id The user's ID
     * @param integer $productId The current product's id
     *
     * @return {object} The user
     */
    public static function get($id, $productId) {
        $userProductTest = self::join('user_products', 'users.id', '=', 'user_products.user_id')
                ->where('users.id', '=', $id)
                ->where('user_products.product_id', '=', $productId)
                ->first();

        if(!$userProductTest) {
            return null;
        }

        $user = self::find($id);
        $user->userProducts;

        return $user;
    }

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
     * @param integer $productId Only get users from this product
     *
     * @return array The list of users, indexed by id
     */
    public static function publicList($productId) {
        $output = [];

        $userIds = self::join('user_products', 'users.id', '=', 'user_products.user_id')
                ->where('user_products.product_id', '=', $productId)
                ->pluck('user_id')
                ->toArray();

        $users = self::whereIn('id', $userIds)->get();
        foreach($users as $user) {
            unset($user['password'], $user['remember_token']);
            $user->userProducts;
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

        //ORMs make everything so easy!
        $query = AccessControl::select();
        foreach($userProducts as $key => $userProduct) {
            $subQueryFunction = function ($subQuery) use ($userProduct) {
                $subQuery->where('product_id', '=', $userProduct->product_id)
                        ->where(function ($subQuery) use ($userProduct) {
                            $subQuery->where('user_id', '=', $userProduct->user_id)
                                    ->orWhere('group_id', '=', $userProduct->group_id);
                        });
            };

            if($key) {
                $query->orWhere($subQueryFunction);
            }
            else {
                $query->where($subQueryFunction);
            }
        }

        return $query->get();
    }

    /**
     * Check if this user has access to the given product.
     *
     * @param integer $productId The product ID to check
     *
     * @return boolean
     */
    public function canAccessProduct($productId) {
        $productIds = $this->userProducts->pluck('product_id')->all();

        return in_array($productId, $productIds);
    }

    /**
     * Select all that belong to the current product.
     *
     * @return {object} The query object
     */
    public static function allForCurrentProduct() {
        $productId = \Auth::user()->ACL->productId;

        return self::allForProduct($productId);
    }

    /**
     * Select all that belong to the specified product.
     *
     * @param integer $productId Limit to this product
     *
     * @return object The query object
     */
    public static function allForProduct($productId) {
        return self::join('user_products', 'users.id', '=', 'user_products.user_id')
                ->where('user_products.product_id', '=', (int)$productId);
    }
}
