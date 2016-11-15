<?php

namespace App;

use DB;
use Log;

use App\AppModel;
use App\AccessControlStructure;

/**
 * Manages ACLs. Defaults to denial. Supports per-group and per-user permission settings. The behavior of this model
 * is expected to match the ACL model in the UI.
 */
class AccessControl extends AppModel {
	/**
	 * @var string $table This model's corresponding database table
	 */
	protected $table = 'access_controls';

	/**
	 * @var string[] $guarded Columns that are protected from writes by other sources
	 */
	protected $guarded = ['id'];

	/**
	 * @var string[] $dates The names of the date columns
	 */
	protected $dates = ['created_at', 'updated_at', 'deleted_at'];

	/**
	 * @var ?integer $productId Check access against this product
	 */
	public $productId = null;

	public $permissions = [];

	/**
	 * Construct the User instance.
	 *
	 * @param ?integer $productId (optional) The ACL's productId
	 */
	public function __construct($productId = null) {
		if($productId) {
			$this->productId = $productId;
		}
	}

	/**
	 * Load the ACL into this model for the given user or the current user if none was provided.
	 *
	 * @param mixed[]|null $user (optional) Load permissions for this user
	 *
	 * @return array[] The user's ACL
	 */
	public function loadPermissions($user = null) {
		//default to the current user if one isn't provided
		if(!$user) {
			$user = \Auth::user();

			if(!$user) {
				throw new \Exception('Missing user.');
			}
		}
		$user = User::find($user['id']);

		$accessControlStructure = new AccessControlStructure();
		$structure = $accessControlStructure->getStructure();
		
		$this->permissions = [];
		$permissions = $user->getPermissions();
		$productIds = $user->userProducts->pluck('product_id')->all();
		foreach($productIds as $productId) {
			$this->permissions[$productId] = self::_applyPermissions($structure, $permissions, $productId);
		}

		return $this->permissions;
	}

	/**
	 * Test if the loaded ACL tree permits the given action specified by the human-readable key.
	 *
	 * @param string $key The key of the permission to test
	 *
	 * @return boolean Can the user do the thing?
	 */
	public function can($key) {
		if(!$this->productId) {
			throw new \Exception('No product ID was set.');
		}

		if(!$this->permissions) {
			$this->loadPermissions();
		}

		$permissions = $this->_getProductPermissions();
		foreach($permissions as $permission) {
			//if permissions key is what we are looking for, resolve user permissions for that node.
			if(strtolower($permission['access_key']) == strtolower($key)) {
				return $this->_resolvePermission($permission);
			}
		}

		return false;
	}

	/**
	 * Overlay the user's permissions onto the ACL structure to produce their ACL.
	 *
	 * @param array[] $structure The ACL structure
	 * @param array[] $permissions The user's permissions
	 * @param integer $productId Build ACL for this product
	 *
	 * @return array[] The ACL
	 */
	protected static function _applyPermissions($structure, $permissions, $productId) {
		$permissions = $permissions->toArray();
		foreach($permissions as $permission) {
			if($permission['product_id'] == $productId) {
				$structure[$permission['access_control_structure_id']]['permitted'] = (bool)$permission['permitted'];
			}
		}

		return $structure;
	}

	/**
	 * Get the permissions for the currently selected product.
	 *
	 * @return array
	 */
	protected function _getProductPermissions() {
		$productId = $this->productId;
		
		return $this->permissions[$productId];
	}

	/**
	 * Recurse down the ACL tree to test if the user can do the thing.
	 *
	 * @param mixed[] $permission The permission we are currently checking
	 * @param array[]|null $permissions (optional) The user's unvisited permissions
	 *
	 * @return boolean Can the user do the thing?
	 */
	protected function _resolvePermission($permission, $permissions = null) {
		if($permissions === null) {
			if(!$this->permissions) {
				$this->loadPermissions();
			}

			$permissions = $this->_getProductPermissions();
		}

		if($permission['permitted']) {
			if(!$permission['parent_id']) {
				return true;
			}
			if(isset($permissions[$permission['parent_id']])) {
				$parent = $permissions[$permission['parent_id']];
				unset($permissions[$permission['parent_id']]);

				return $this->_resolvePermission($parent, $permissions);
			}
			else {
				if(isset($this->permissions[$permission['parent_id']])) {
					Log::warning('Permission cycle detected on permission id ' . $permission['parent_id'] . '.');
				}
				else {
					Log::warning('Could not find permission parent id ' . $permission['parent_id'] . '.');
				}
			}
		}

		return false;
	}
}