<?php

namespace App;

use App\AppModel;

class Group extends AppModel {
    protected $table = 'groups';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'title'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [];

    /**
     * Get a group from the specified product.
     *
     * @param integer $id The group's ID
     * @param integer $productId The product's id
     *
     * @return object|null The group
     */
    public static function get($id, $productId) {
        return self::where('id', '=', $id)
                ->where('product_id', '=', $productId)
                ->first();
    }

    /**
     * Get a group's level in the specified product.
     *
     * @param integer $id The group's ID
     * @param integer $productId The product's id
     *
     * @return integer The group
     */
    public static function getLevel($id, $productId) {
        $group = self::get($id, $productId);

        return $group ? $group->level : -1;
    }
}
