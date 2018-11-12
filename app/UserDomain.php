<?php

namespace App;

use App\AppModel;

class UserDomain extends AppModel {
    protected $table = 'users_domains';
    protected $guarded = ['id'];

    /**
	 * Find the user_id based on known domianId and $groupId.
	 *
	 * @param integer $domainId
	 *
	 * @return ?integer
	 */
	public static function getUserIds($domainId, $groupId) {
        $userIds = [];
        $uniqueUserIds = [];
        $userDomains = self::where('domain_id', '=', $domainId)
            ->where('group_id', '=', $groupId)
            ->get();
			foreach($userDomains as $userDomain) {
                $userIds[] = $userDomain['user_id'];
			}
            $uniqueUserIds = array_unique($userIds);
			return $uniqueUserIds;

	}
}
