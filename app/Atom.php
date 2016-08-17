<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\AppModel;
use App\FuzzyRank;
use App\Assignment;

class Atom extends AppModel {
    use SoftDeletes;

    /**
     * @var string This model's corresponding database table
     */
    protected $table = 'atoms';

    /**
     * @var string[] Columns that are protected from writes by other sources
     */
    protected $guarded = ['id'];

    /**
     * @var string[] The names of the date columns
     */
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * @var string[] The names and prefixes for the elements that we want to assign IDs to
     */
    protected static $idPrefixes = [
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

    /**
     * Save this atom. Automatically updates meta data, and assigns IDs to XML elements when appropriate.
     *
     * @param array $options
     *
     * @return void
     */
    public function save(array $options = []) {
        $this->updateTitle();
        $this->xml = self::assignXMLIds($this->xml);
        $this->modifiedBy = \Auth::user()['id'];
        parent::save($options);
    }

    /**
     * Extracts the title from the atom's XML, cleans it up, and places it into meta data columns.
     *
     * @return void
     */
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

    /**
     * Assign IDs to XML elements where appropriate.
     *
     * @param string $xml The XML to operate on
     *
     * @return string The modified XML
     */
    public static function assignXMLIds($xml) {
        $tagRegex = '/<[^\/<>]+>/S';
        $nameRegex = '/<([^\s<>]+).*?>/S';
        $idSuffixRegex = '/\bid="[^"]*?(\d+)"/Si';
        $idReplaceableSuffixRegex = '/_REPLACE_ME__/S';
        $idRegex = '/\bid="[^"]*"/Si';

        //remove empty ids
        $xml = str_replace(' id=""', '', $xml);

        //remove the first id -- it will be added during export
        $xml = self::removeAtomIDFromXML($xml);

        //initialize $idSuffix
        $idSuffix = 0;
        preg_match_all($tagRegex, $xml, $tags);
        $tags = $tags[0];
        foreach($tags as $key => $tag) {
            //skip the tags we don't care about
            $name = strtolower(preg_replace($nameRegex, '$1', $tag));
            if(!isset(self::$idPrefixes[$name])) {
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
            $prefix = self::$idPrefixes[$name];
            $id = $prefix . ++$idSuffix;
            $newTag = substr($tag, 0, strlen($tag) - 1) . ' id="' . $id . '">';
            $tag = preg_quote($tag, '/');
            $xml = preg_replace('/' . $tag . '/', $newTag, $xml, 1);
        }

        //yes, we need to do this again in order to keep automatic IDs from creeping in
        $xml = self::removeAtomIDFromXML($xml);

        return $xml;
    }

    /**
     * Attempt to find the atom's entityId in its XML
     *
     * @param string $xml The XML to operate on
     *
     * @return ?string The detected entityId
     */
    public static function detectAtomIDFromXML($xml) {
        $prefixPartial = '(' . implode('|', self::$idPrefixes) . ')';
        preg_match('/^(\s*<[^>]*) id="' . $prefixPartial . '([^"]*)"/Si', $xml, $match);

        return (isset($match[3]) && $match[3] != '_REPLACE_ME__') ? $match[3] : null;
    }

    /**
     * Attempt to remove the atom's entityId from its XML
     *
     * @param string $xml The XML to operate on
     *
     * @return ?string The detected entityId
     */
    public static function removeAtomIDFromXML($xml) {
        return preg_replace('/^(\s*<[^>]*) id="[^"]*"/Si', '$1', $xml);
    }

    /**
     * Generate a UID for use as an entityId.
     *
     * @return string The UID
     */
    public static function makeUID() {
        return str_replace('.', '', uniqid('', true));
    }

    /**
     * Get a list of the latest version of every atom that hasn't been deleted.
     *
     * @return string[] The IDs of all current atoms
     */
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

    /**
     * Get a list of the latest version of every atom that hasn't been deleted.
     *
     * @param string $query The user's search query
     * @param int $limit (optional) Max number of results per page
     * @param int $page (optional) The results page to retrieve
     *
     * @return string[] The IDs of all current atoms
     */
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

    /**
     * Get the latest version of an atom regardless of whether or not it has been deleted.
     *
     * @param string|string $entityId The entityId(s) of the atom
     *
     * @return mixed|mixed[]|null The atom(s)
     */
    public static function findNewest($entityId) {
        if(is_array($entityId)) {      //plural
            return self::whereIn('id', self::latestIDs())
                    ->whereIn('entityId', $entityId);
        }
        else {      //singular
            return self::withTrashed()
                    ->where('entityId', '=', $entityId)
                    ->orderBy('id', 'desc')
                    ->first();
        }
    }

    /**
     * Get the latest versions of a list of atoms regardless of whether or not it has been deleted.
     *
     * @param string[] $entityIds The entityId of the atom
     *
     * @return mixed[]|null The atom
     */
    public static function findNewestInList($entityIds) {
        $atom = self::withTrashed()
                ->where('entityId', '=', $entityId)
                ->orderBy('id', 'desc')
                ->first();

        return $atom;
    }

    /**
     * Get the latest version of an atom or null if it has been deleted.
     *
     * @param string $entityId The entityId of the atom
     *
     * @return mixed[]|null The atom
     */
    public static function findNewestIfNotDeleted($entityId) {
        $atom = self::withTrashed()
                ->where('entityId', '=', $entityId)
                ->orderBy('id', 'desc')
                ->first();

        return ($atom && $atom->trashed()) ? null : $atom;
    }

    /**
     * Add active assignments to the atom.
     *
     * @return object This object
     */
    public function addAssignments() {
        $this->assignments = self::getAssignments($this->entityId)['assignments'];

        return $this;
    }

    /**
     * Get active assignments for the given atom entityId.
     *
     * @param string $entityId The atom's entityId
     *
     * @return object[] The assignments
     */
    public static function getAssignments($entityId) {
        return (new Assignment)->getList([
            'atomEntityId' => $entityId
        ]);
    }
}
