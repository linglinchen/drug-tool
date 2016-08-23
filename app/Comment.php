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
     * Get comments for the given atom entityId(s).
     *
     * @param string|string[] $entityId The atom's entityId(s)
     *
     * @return object[] The comments
     */
    protected static function getByAtomEntityId($entityId) {
        if(is_array($entityId)) {
            $comments = self::whereIn('atomEntityId', $entityId);
        }
        else {
            $comments = self::where('atomEntityId', '=', $entityId);
        }

        $comments = $comments->get()
                ->toArray();

        return $comments;
    }
}
