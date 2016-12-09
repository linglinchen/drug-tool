<?php

namespace App;

use App\AppModel;

class UserProduct extends AppModel {
    protected $table = 'user_products';
    protected $dates = ['created_at', 'updated_at'];

	/**
	 * Find the user's group_id in the specified product if it exists.
	 *
	 * @param integer $userId
	 * @param ?integer $productId The first available row will be used if no product is specified
	 *
	 * @return ?integer
	 */
	public static function getGroupId($userId, $productId = null) {
		$query = self::where('user_id', '=', $userId);

		if($productId) {
			$query->where('product_id', '=', $productId);

			return $query->first();
		}
		else {
			$groupIds = [];
			$userProducts = $query->get();
			foreach($userProducts as $userProduct) {
				$groupIds[$userProduct['product_id']] = $userProduct['group_id'];
			}
			
			return $groupIds;
		}
	}
}
