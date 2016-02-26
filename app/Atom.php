<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Atom extends Model
{
    use SoftDeletes;
    
    protected $table = 'atoms';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public static function findNewestIfNotDeleted($atomId) {
        $atom = self::withTrashed()
                ->where('atomId', '=', $atomId)
                ->orderBy('id', 'desc')
                ->first();

        return $atom->trashed() ? null : $atom;
    }
}
