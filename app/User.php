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

    /** NEWVERSION
     * Returns a list of all users with sensitive fields excluded.
     *
     * @return array The list of users, indexed by id
     */
    public static function publicList() {
        $output = [];
//added 'with' here to bring in userProducts. This works only because there is a relationship to userProducts setup in this model.
        $users = self::with('userProducts')->select()->get();
        //print_r($users);
     foreach($users as $user) {
           unset($user['password'], $user['remember_token']);
//this part of the loop was an additional database call., now removed
//            $user->userProducts;
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
//userProducts creates a whole collection .. for pluck, maybe a select('product_id')->get()
       // print_r($userProducts);
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
