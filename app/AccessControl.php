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
	 * Load the ACL into this model for the given user or the current user if none was provided.
	 *
	 * @param mixed[]|null $user (optional) Load permissions for this user
	 *
	 * @return array[] The user's ACL
	 */
	public function loadPermissions($user = null) {
		//default to the current user if one isn't provided
		if($user === null) {
			$user = \Auth::user();
		}

		$query = self::where('user_id', '=', $user['id']);
		if($user['group_id']) {
			$query->orWhere('group_id', '=', $user['group_id']);
		}

		$permissions = $query->get();
		$accessControlStructure = new AccessControlStructure();
		$structure = $accessControlStructure->getStructure();
		$this->permissions = self::_applyPermissions($structure, $permissions);

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
		if(!$this->permissions) {
			$this->loadPermissions();
		}

		foreach($this->permissions as $permission) {
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
	 * @param string $structure array[] The ACL structure
	 * @param string $permissions array[] The user's permissions
	 *
	 * @return array[] The ACL
	 */
	protected static function _applyPermissions($structure, $permissions) {
		foreach($permissions as $permission) {
			$structure[$permission['access_control_structure_id']]['permitted'] = (bool)$permission['permitted'];
		}

		return $structure;
	}

	/**
	 * Recurse down the ACL tree to test if the user can do the thing.
	 *
	 * @param string $permission mixed[] The permission we are currently checking
	 * @param string $permissions array[]|null (optional) The user's unvisited permissions
	 *
	 * @return boolean Can the user do the thing?
	 */
	protected function _resolvePermission($permission, $permissions = null) {
		if($permissions === null) {
			if(!$this->permissions) {
				$this->loadPermissions();
			}

			$permissions = $this->permissions;
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