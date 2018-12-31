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

    const TOKEN_EXPIRATION_TIME = 86400;        //1 day

    public static $editableFields = ['firstname', 'lastname', 'email', 'new_password'];
    public static $adminModifiableFields = ['firstname', 'lastname'];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token', 'ACL', 'reset_token', 'reset_token_expiration'
    ];

    public $ACL;

    /**
     * Get a user that has access to the specified product.
     *
     * @param integer $id The user's ID
     * @param integer $productId The current product's id
     * @param boolean $includeOrphaned=false (optional) Include users who don't belong to a product?
     *
     * @return object|null The user
     */
    public static function get($id, $productId, $includeOrphaned = false) {
        $userProductTest = self::join('user_products', 'users.id', '=', 'user_products.user_id')
                ->where('users.id', '=', $id)
                ->where('user_products.product_id', '=', $productId)
                ->first();

        if(!$userProductTest) {
            if(!$includeOrphaned) {
                return null;
            }

            $orphanedUserTest = self::join('user_products', 'users.id', '=', 'user_products.user_id', 'left outer')
                    ->where('users.id', '=', $id)
                    ->whereNull('user_products')
                    ->first();

            if(!$orphanedUserTest) {
                return null;
            }
        }

        $user = self::find($id);
        $user->userProducts;

        return $user;
    }

    /**
     * Get a user by their email address.
     *
     * @param string $email The user's email
     *
     * @return object|null The user
     */
    public static function getByEmail($email) {
        return self::where('email', '=', $email)->first();
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
     * Set up the UserDomain relationship.
     *
     * @returns HasManyThrough
     */
    public function userDomains() {
        return $this->hasMany('App\UserDomain');
    }

    /**
     * Returns a list of all users with sensitive fields excluded.
     *
     * @param integer $productId Get users from this product
     * @param boolean $includeOrphaned=false (optional) Include users who don't belong to a product?
     *
     * @return array The list of users, indexed by id
     */
    public static function publicList($productId, $includeOrphaned = false) {
        $output = [];

        $userIds = self::join('user_products', 'users.id', '=', 'user_products.user_id')
                ->where('user_products.product_id', '=', $productId)
                ->pluck('users.id')
                ->toArray();

        if($includeOrphaned) {
            $orphanedUserIds = self::join('user_products', 'users.id', '=', 'user_products.user_id', 'left outer')
                    ->whereNull('user_products')
                    ->pluck('users.id')
                    ->toArray();
            $userIds = array_merge($userIds, $orphanedUserIds);
        }

        $users = self::whereIn('id', $userIds)->get();
        foreach($users as $user) {
            unset($user['password'], $user['remember_token']);
            $user->userProducts;
            $user->userDomains;
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
                ->join('users_domains', 'users.id', '=', 'users_domains.user_id')
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
     * @param object $targetUser The user being modified
     * @param integer [$productId] Limit to this product
     *
     * @return boolean
     */
    public function canModify($targetUser, $productId = null) {
        $acl = $this->ACL;
        $editingSelf = $targetUser->id == $this->id;

        if($productId === null) {
            $targetUserLevel = $targetUser->getHighestAdminLevel();
            $userLevel = $this->getHighestAdminLevel();
        }
        else {
            $targetUserGroup = $targetUser->getGroup($productId);
            $targetUserLevel = $targetUserGroup ? $targetUserGroup->level : -1;
            $userGroup = $this->getGroup($productId);
            $userLevel = $userGroup ? $userGroup->level : -1;
        }

        return $editingSelf || ($acl->can('manage_users') && $targetUserLevel < $userLevel);
    }

    /**
     * Get this user's highest admin level across all products.
     *
     * @return integer
     */
    public function getHighestAdminLevel() {
        $level = -1;
        foreach($this->userProducts as $userProduct) {
            $group = Group::find($userProduct->group_id);
            if($group) {
                $level = max($level, $group->level);
            }
        }

        return $level;
    }

    /**
     * Check if this user is an admin in any product.
     *
     * @return {boolean} Is it an admin?
     */
    public function isAdminAnywhere() {
        return $this->getHighestAdminLevel() > 0;
    }

    /**
     * Set the user's reset token and its expiration time.
     *
     * @return object The user
     */
    public function setResetToken() {
        $this->reset_token = self::_makeToken();
        $this->reset_token_expiration = date(\DateTime::ISO8601, time() + self::TOKEN_EXPIRATION_TIME);
        $this->save();

        return $this;
    }

    /**
     * Destroy the user's reset token and its expiration time.
     *
     * @return object The user
     */
    public function destroyResetToken() {
        $this->reset_token = null;
        $this->reset_token_expiration = null;
        $this->save();

        return $this;
    }

    /**
     * Get the user with the specified reset token if it has not expired.
     *
     * @param string $token The user's reset token
     *
     * @return ?object The user
     */
    public static function getByToken($token) {
        return self::where('reset_token', '=', $token)
                ->where('reset_token_expiration', '>', date(\DateTime::ISO8601))
                ->first();
    }

    /**
     * Send a password reset email to the user.
     *
     * @return object The user
     */
    public function sendResetEmail() {
        //TODO: make this do something

        return $this;
    }

    /**
     * Generate a UID for use as an entityId.
     *
     * @return string The UID
     */
    protected static function _makeToken() {
        return str_replace('.', '', uniqid('', true));
    }
}
