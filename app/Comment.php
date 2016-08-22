<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Comment extends AppModel {
    use SoftDeletes;

    protected $table = 'comments';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Get comments for the given atom entityId.
     *
     * @param string $entityId The atom's entityId
     *
     * @return object[] The comments
     */
    protected static function getByAtomEntityId($entityId) {
        $comments = self::where('atomEntityId', '=', $entityId)
                ->get()
                ->toArray();

        return self::toTree($comments);
    }
}
