<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\FuzzyRank;

class Atom extends Model {
    use SoftDeletes;

    protected $table = 'atoms';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    public function save(array $options = []) {
        $this->updateTitle();
        $this->xml = self::assignXMLIds($this->xml);
        parent::save($options);
    }

    public function updateTitle() {
        $titleElements = ['group_title', 'mono_name'];      //must be in order of priority

        foreach($titleElements as $titleElement) {
            preg_match('/<' . $titleElement . '>(.*)<\/' . $titleElement . '>/i', $this->xml, $match);

            if($match) {
                $this->title = $match[1];
                break;
            }
        }

        if(!$match) {
            return;
        }

        $this->title = trim($this->title);
        $this->alphaTitle = mb_convert_encoding(strip_tags($this->title), 'ASCII');
    }

    public static function assignXMLIds($xml) {
        $idPrefixes = [
            'group' => 'g',
            'monograph' => 'm',
            'list' => 'l',
            'section' => 's',
            'para' => 'p',
            'table' => 't',
            'tgroup' => 'tg',
            'row' => 'r',
            'pill' => 'pl'
        ];

        $tagRegex = '/<[^\/<>]+>/S';
        $nameRegex = '/<([^\s<>]+).*?>/S';
        $idSuffixRegex = '/\bid="[^"]*?(\d+)"/Si';
        $idReplaceableSuffixRegex = '/__REPLACE_ME__/S';
        $idRegex = '/\bid="[^"]*"/Si';

        //remove empty ids
        $xml = str_replace(' id=""', '', $xml);

        //initialize $idSuffix
        $idSuffix = 0;
        preg_match_all($tagRegex, $xml, $tags);
        $tags = $tags[0];
        foreach($tags as $key => $tag) {
            //skip the tags we don't care about
            $name = strtolower(preg_replace($nameRegex, '$1', $tag));
            if(!isset($idPrefixes[$name])) {
                unset($tags[$key]);
                continue;
            }

            preg_match($idSuffixRegex, $tag, $id);
            if($id) {
                $id = (int)$id[1];
                $idSuffix = $idSuffix > $id ? $idSuffix : $id;
            }
        }

        //complete id replacements
        $old = '';
        $new = $xml;
        while($old != $new) {
            $old = $new;
            $new = preg_replace($idReplaceableSuffixRegex, ++$idSuffix, $old, 1);
        }
        if($old) {
            --$idSuffix;
        }
        $xml = $new;

        //assign the missing ids
        foreach($tags as $tag) {
            if(preg_match($idRegex, $tag)) {
                continue;       //it already has an id
            }

            $name = strtolower(preg_replace($nameRegex, '$1', $tag));
            $prefix = $idPrefixes[$name];
            $id = $prefix . ++$idSuffix;
            $newTag = substr($tag, 0, strlen($tag) - 1) . ' id="' . $id . '">';
            $tag = preg_quote($tag, '/');
            $xml = preg_replace('/' . $tag . '/', $newTag, $xml, 1);
        }

        return $xml;
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

        return ($atom && $atom->trashed()) ? null : $atom;
    }
}
