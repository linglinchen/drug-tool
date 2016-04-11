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
        $sql = 'select id
                from atoms
                where id in (
                        select max(id)
                        from atoms
                        group by "atomId"
                    )
                    and deleted_at is null';
        $results = DB::select($sql);

        $list = [];
        foreach($results as $row) {
            $list[] = $row->id;
        }

        return $list;
    }

    public static function search($query) {
        $queryTitleConditions = [];
        $queryStrippedTitleConditions = [];
        $explodedQuery = preg_split('/\s+/', trim($query));
        foreach($explodedQuery as $queryPart) {
            $queryTitleConditions[] = [DB::raw('lower(title)'), 'like', '%' . $queryPart . '%'];
            $queryStrippedTitleConditions[] = [DB::raw('lower("strippedTitle")'), 'like', '%' . $queryPart . '%'];
        }

        return self::whereIn('id', self::latestIDs())
                ->where($queryTitleConditions)
                ->orWhere($queryStrippedTitleConditions);
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
