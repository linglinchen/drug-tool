<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

use DB;
use Log;

use App\AccessControlStructure;

class AccessControl extends Model {
	protected $table = 'access_controls';
	protected $guarded = ['id'];
	protected $dates = ['created_at', 'updated_at', 'deleted_at'];

	public function loadPermissions($user = null) {
		//default to the current user if one isn't provided
		if($user === null) {
			$user = \Auth::user();
		}

		$permissions = self::where('userId', '=', $user['id'])
				->orWhere('groupId', '=', $user['groupId'])
				->get();

		$accessControlStructure = new AccessControlStructure();
		$structure = $accessControlStructure->getStructure();
		$this->permissions = self::_applyPermissions($structure, $permissions);

		return $this->permissions;
	}

	public function can($key) {
		if(!$this->permissions) {
			$this->loadPermissions();
		}

		foreach($this->permissions as $permission) {
			//if permissions key is what we are looking for, resolve user permissions for that node.
			if(strtolower($permission['accessKey']) == strtolower($key)) {
				return $this->_resolvePermission($permission);
			}
		}

		return false;
	}

	protected static function _applyPermissions($structure, $permissions) {
		foreach($permissions as $permission) {
			$structure[$permission['accessControlStructureId']]['permitted'] = (bool)$permission['permitted'];
		}

		return $structure;
	}

	protected function _resolvePermission($permission, $permissions = null) {
		if($permissions === null) {
			if(!$this->permissions) {
				$this->loadPermissions();
			}

			$permissions = $this->permissions;
		}

		if($permission['permitted']) {
			if(!$permission['parentId']) {
				return true;
			}
			if(isset($permissions[$permission['parentId']])) {
				$parent = $permissions[$permission['parentId']];
				unset($permissions[$permission['parentId']]);

				return $this->_resolvePermission($parent, $permissions);
			}
			else {
				if(isset($this->permissions[$permission['parentId']])) {
					Log::warning('Permission cycle detected on permission id ' . $permission['parentId'] . '.');
				}
				else {
					Log::warning('Could not find permission parent id ' . $permission['parentId'] . '.');
				}
			}
		}

		return false;
	}
}