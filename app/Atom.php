<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\FuzzyRank;

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

    public static function search($query, $limit = 10, $page = 1) {
        $sanitizer = '/[^a-z0-9_.]/Si';
        $queryTitleConditions = [];
        $queryalphaTitleConditions = [];

        $query = trim(preg_replace($sanitizer, ' ', $query));
        $explodedQuery = preg_split('/\s+/', $query);
        foreach($explodedQuery as $queryPart) {
            $queryTitleConditions[] = [DB::raw('lower(title)'), 'like', '%' . $queryPart . '%'];
            $queryalphaTitleConditions[] = [DB::raw('lower("alphaTitle")'), 'like', '%' . $queryPart . '%'];
        }

        //need to get the unranked list of candidates first
        $candidates = self::whereIn('id', self::latestIDs())
                ->where(function ($query) use ($queryTitleConditions, $queryalphaTitleConditions) {
                    $query->where($queryTitleConditions)
                            ->orWhere($queryalphaTitleConditions);
                })
                ->lists('alphaTitle', 'id')
                ->all();

        $candidates = FuzzyRank::rank($candidates, $query);
        $count = sizeof($candidates);
        $candidates = array_keys($candidates);
        $candidates = array_slice($candidates, ($page - 1) * $limit, $limit);       //handling paging outside of sql for better performance

        return [
            'count' => $count,
            'atoms' => self::whereIn('id', $candidates)->get()
        ];
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
