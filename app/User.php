<?php

namespace App;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Hash;

use App\AccessControl;

class User extends Authenticatable {
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstname', 'lastname', 'email', 'password',
    ];

    public static $editableFields = ['firstname', 'lastname', 'email', 'new_password'];
    public static $adminModifiableFields = ['firstname', 'lastname'];


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
     * @return object|null The user
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
            $user->domain;
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

    /**
     * Check if a password meets our minimum requirements.
     *
     * @param string $password The password we are checking
     *
     * @return void
     *
     * @throws Exception If the password is invalid
     */
    public static function validatePassword($password) {
        if(strlen($password) < 8) {
            throw new \Exception('Password must be at least 8 characters.');
        }
        if(!preg_match('/\d/', $password)) {
            throw new \Exception('Password must contain as least 1 number.');
        }
        if(!preg_match('/[a-z]/i', $password)) {
            throw new \Exception('Password must contain as least 1 letter.');
        }
        if(!preg_match('/[a-z]/', $password) || !preg_match('/[A-Z]/', $password)) {
            throw new \Exception('Password must contain as least 1 capital and 1 lowercase letter.');
        }
    }

    /**
     * Check if this user is in a valid state for saving.
     *
     * @return void
     *
     * @throws Exception If the user is invalid
     */
    public function validate() {
        if(!trim($this->firstname)) {
            throw new \Exception('First name is required.');
        }

        if(!trim($this->lastname)) {
            throw new \Exception('First name is required.');
        }

        if(!preg_match('/^[^@]+@[^@]+\.[^@]+$/', $this->email)) {
            throw new \Exception('A valid email address is required.');
        }

        if(isset($this->new_password)) {
            self::validatePassword($this->new_password);
        }
    }

    /**
     * Get the user's group in the specified product.
     *
     * @param integer $productId Limit to this product
     *
     * @return ?object The user's group
     */
    public function getGroup($productId) {
        foreach($this->userProducts as $userProduct) {
            if($userProduct->product_id == $productId) {
                return Group::find($userProduct->group_id);
            }
        }

        return null;
    }

    /**
     * Check the user's validity, and save.
     */
    public function save(array $options = []) {
        $this->validate();

        if(isset($this->new_password)) {
            $this->password = Hash::make($this->new_password);
            unset($this->new_password);
        }

        parent::save($options);
    }

    /**
     * Can the specified admin user modify the target user?
     *
     * @param object $adminUser The user requesting permission
     * @param object $targetUser The user being modified
     *
     * @return boolean
     */
    public static function canModify($adminUser, $targetUser) {
        $acl = $adminUser->ACL;
        $editingSelf = $targetUser->id == $adminUser->id;

        $targetUserGroup = $targetUser->getGroup($productId);
        $targetUserLevel = $targetUserGroup ? $targetUserGroup->level : -1;
        $adminUserGroup = $adminUser->getGroup($productId);
        $adminUserLevel = $adminUserGroup ? $adminUserGroup->level : -1;

        return !$editingSelf && !($acl->can('manage_users') && $targetUserLevel < $adminUserLevel);
    }
}
