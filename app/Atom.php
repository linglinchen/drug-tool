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

    public function save(array $options = []) {
        $this->updateTitle();
        parent::save($options);
    }

    public function updateTitle() {
        preg_match('/<mono_name>(.*)<\/mono_name>/i', $this->xml, $match);
        if($match) {
            $this->title = $match[1];
        }

        $this->title = trim($this->title);
        $this->alphaTitle = strip_tags($this->title);
    }

    public static function makeUID() {
        return uniqid('', true);
    }

    public static function latestIDs() {
        $sql = 'select id
                from atoms
                where id in (
                        select max(id)
                        from atoms
                        group by "entityId"
                    )
                    and deleted_at is null
                order by "alphaTitle"';
        $results = DB::select($sql);

        $list = [];
        foreach($results as $row) {
            $list[] = $row->id;
        }

        return $list;
    }

    public static function search($query, $limit = 10) {
        $queryTitleConditions = [];
        $queryalphaTitleConditions = [];
        $explodedQuery = preg_split('/\s+/', trim($query));
        foreach($explodedQuery as $queryPart) {
            $queryTitleConditions[] = [DB::raw('lower(title)'), 'like', '%' . $queryPart . '%'];
            $queryalphaTitleConditions[] = [DB::raw('lower("alphaTitle")'), 'like', '%' . $queryPart . '%'];
        }

        return self::whereIn('id', self::latestIDs())
                ->where(function ($query) use ($queryTitleConditions, $queryalphaTitleConditions) {
                    $query->where($queryTitleConditions)
                            ->orWhere($queryalphaTitleConditions);
                })
                ->paginate($limit);
    }

    public static function findNewest($entityId) {
        $atom = self::withTrashed()
                ->where('entityId', '=', $entityId)
                ->orderBy('id', 'desc')
                ->first();

        return $atom;
    }

    public static function findNewestIfNotDeleted($entityId) {
        $atom = self::withTrashed()
                ->where('entityId', '=', $entityId)
                ->orderBy('id', 'desc')
                ->first();

        return $atom->trashed() ? null : $atom;
    }
}
