<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

class Atom extends Model
{
    use SoftDeletes;
    
    protected $table = 'atoms';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public static function makeUID() {
        return uniqid('', true);
    }

    public static function latestIDs() {
        $results = DB::select(
            'select id
            from atoms
            where id in (
                select max(id)
                from atoms
                group by "atomId"
            )');

        $list = [];
        foreach($results as $row) {
            $list[] = $row->id;
        }

        return $list;
    }

    public static function findNewest($atomId) {
        $atom = self::withTrashed()
                ->where('atomId', '=', $atomId)
                ->orderBy('id', 'desc')
                ->first();

        return $atom;
    }

    public static function findNewestIfNotDeleted($atomId) {
        $atom = self::withTrashed()
                ->where('atomId', '=', $atomId)
                ->orderBy('id', 'desc')
                ->first();

        return $atom->trashed() ? null : $atom;
    }
}
