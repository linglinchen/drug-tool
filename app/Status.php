<?php

namespace App;

use App\AppModel;

class Status extends AppModel {
    protected $table = 'statuses';
    protected $guarded = ['id'];

    /**
     * Get a list of statuses that are considered ready for publication within the specified product.
     *
     * @param integer $productId Limit to this product
     *
     * @return integer[]
     */
    public static function getReadyForPublicationStatuses($productId) {
        return self::allForProduct($productId)
                ->where('publish', '=', 1)
                ->where('active', '=', 1)
                ->get()
                ->pluck('id')
                ->all();
    }

    /**
     * Get a list of statuses that are considered trashed within the specified product.
     *
     * @param integer $productId Limit to this product
     *
     * @return integer[]
     */
    public static function getTrashedStatuses($productId) {
        return self::allForProduct($productId)
                ->where('active', '=', 0)
                ->get()
                ->pluck('id')
                ->all();
    }


    public static function getDeactivatedStatusId($productId) {
         return self::allForProduct($productId)
            ->where('title', '=', 'Deactivated')
            ->first();
    }

    public static function getDevStatusId($productId){
        return self::allForProduct($productId)
            ->where('title', '=', 'Development')
            ->first();
    }
}
