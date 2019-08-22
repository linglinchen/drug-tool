<?php

namespace App;

use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

use App\AppModel;
use App\FuzzyRank;
use App\Assignment;
use App\Comment;
use App\Molecule;
use App\User;
use App\Product;
use App\Status;

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
     * @var string[] The names of database columns that we don't consider worth automatically changing the status or
     *     creating a new atom version for
     */
    protected static $insignificantColumns = [
        'id',
        'molecule_code',
        'modified_by',
        'created_at',
        'updated_at',
        'deleted_at',
        'status_id',
        'sort',
        'product_id',
        'domain_code',
        'edition'
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
        $doctype = Product::find($this->product_id)->getDoctype();
        $this->xml = $doctype->assignXMLIds($this->xml);
        $this->modified_by = \Auth::user()['id'];
        $autoStatus = array_get($options, 'autoStatus', true);

        if(!$this->alpha_title) {
            throw new \Exception('Missing title.');
        }

        $previousVersion = ($this->entity_id && $this->product_id) ?
                self::findNewest($this->entity_id, $this->product_id) :
                null;

        if($autoStatus && $this->_hasSignificantChanges()) {
            $devStatusId = Status::getDevStatusId($this->product_id)->id;
            $this->status_id = $devStatusId; //change status to be development when saving
        }

        if (!$previousVersion){ //new atom
            $this->xml = preg_replace('/<qnum>NEW<\/qnum>/i', '<qnum>'.$this->alpha_title.'</qnum>', $this->xml);
            if ($this->_isTitleInUse()) {
                $usedTitle = $this->alpha_title;
                $usedId = $this->entity_id;
                throw new \Exception(
                    'That title  ' . $usedTitle . ' with entityId ' . $usedId . ' is already used by another atom within ' .
                    'this product.'
                );
            }
        }

        $doctype->beforeSave($this);
        parent::save($options);
    }

    /**
     * Save this atom in simple way, e.g. when only sort order of atom changes. Does not generate a new version of the
     * atom.
     *
     * @param array $options
     *
     * @return void
     */
    public function simpleSave(array $options = []) {
        parent::save($options);
    }

    /**
     * Extracts the title from the atom's XML, cleans it up, and places it into meta data columns.
     *
     * @return void
     */
    public function updateTitle() {
        $doctype = Product::find($this->product_id)->getDoctype();
        $this->title = $doctype->detectTitle($this);
        $this->alpha_title = self::makeAlphaTitle($this->title);
    }

    /**
     * convert the non-latin characters to latin, trim and strip xml tags to make it more searchable
     *
     * @param string $title the mono_name or group_name in xml
     *
     * @return string alpha title
     */
    public static function makeAlphaTitle($title){
        $trimmedTitle = trim($title);
        $alphaTitle = mb_convert_encoding(strip_tags($trimmedTitle), 'UTF-8');

        return $alphaTitle;
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
	 * Build a query to find the latest version of every atom that hasn't been *deleted* (not same as "deactivated" status)
	 *
	 * @param ?integer|integer[] $statusId (optional) Only return atoms with the specified status(es) (accepts 0-prefixed patterns)
	 * @param ?object $q (optional) Subquery object
	 *
	 * @return object The constructed query object
	*/
	public static function buildLatestIDQuery($statusId = null, $q = null) {
		$table = (new self)->getTable();
		$statusId = is_array($statusId) ? $statusId : ($statusId === null ? null : [$statusId]);

		//limit the search to current product to cut down on database effort
		$currentProductId = (int)self::getCurrentProductId();

		//prefixing with '0' will fetch statuses based on the naming convention pattern established on product 1
		$statusCount = true;
		if($statusId !== null) {
			foreach($statusId as &$statusPattern) {
				if(substr($statusPattern, 0, 1) == '0') {
					switch ($statusPattern) {
						case '0100':
							$statusPattern = Status::getDevStatusId($currentProductId)->id;
							break;
						case '0200':
							$statusPattern = Status::getReadyForPublicationStatusId($currentProductId)->id;
							break;
						case '0300':
							$statusPattern = Status::getDeactivatedStatusId($currentProductId)->id;
							break;
						default:
							$statusCount = false;
					}
				}
			}
		}

		if(!$statusCount) {
			throw new \Exception('Invalid status ID.');
		}

		$query = $q ? $q->select('id') : self::select('id');
		$query->from($table);

		$query->whereIn('id', function ($q) use ($table, $statusId, $currentProductId) {
				$q->select(DB::raw('MAX(id)'))
					->from($table)->where('product_id', '=', $currentProductId);
				$q->groupBy('entity_id');
			})
			->whereNull('deleted_at');
			//->orderBy('alpha_title', 'ASC');

		if($statusId !== null && (!is_array($statusId) || sizeof($statusId))) {
			$query->whereIn('status_id', $statusId);
		}

		return $query;
	}
    /**
     * Legacy version of build a query to find the latest version of every atom that hasn't been deleted.
     *  Used by reports and potentially other functions that pass different variables
     * @param ?integer|integer[] $statusId (optional) Only return atoms with the specified status(es)
     * @param ?object $q (optional) Subquery object
     *
     * @return object The constructed query object
     */
    public static function legacyBuildLatestIDQuery($statusId = null, $q = null) {
        $table = (new self)->getTable();
        $statusId = is_array($statusId) ? $statusId : ($statusId === null ? null : [$statusId]);

        $query = $q ? $q->select('id') : self::select('id');
        $query->from($table);

        $query->whereIn('id', function ($q) use ($table, $statusId) {
                    $q->select(DB::raw('MAX(id)'))
                            ->from($table);

                    if($statusId !== null && (!is_array($statusId) || sizeof($statusId))) {
                        $q->whereIn('status_id', $statusId);
                    }

                    $q->groupBy('entity_id');
                })
                ->whereNull('deleted_at')
                ->orderBy('alpha_title', 'ASC');
        return $query;
    }

    /**
     * Get a list of discontinued monographs.
     *
     * @param integer $productId Limit to this product
     *
     * @return object
     */
    public static function getDiscontinuedMonographs($productId) {
        $sql = 'SELECT id, entity_id, title, UNNEST(XPATH(\'//monograph[@status="discontinued"]/mono_name/text()\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', xml, \'</root>\')))) AS subtitle
                FROM atoms
                WHERE id IN(' . self::buildLatestIDQuery()->toSql() . ')
                    AND product_id=' . $productId . '
                    AND XPATH_EXISTS(\'//monograph[@status="discontinued"]\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', xml, \'</root>\')))';

        return DB::select($sql);
    }

    /**
     * Count all monographs inside active atoms, even if they are grouped.
     *
     * @param integer $productId Limit to this product
     *
     * @return integer
     */
    public static function countMonographs($productId) {
        $sql = 'SELECT COUNT(*)
                FROM (
                    SELECT UNNEST(XPATH(\'//monograph/mono_name\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', xml, \'</root>\'))))
                    FROM atoms
                    WHERE id IN(' . self::buildLatestIDQuery()->toSql() . ')
                        AND product_id=' . $productId . '
                ) AS subquery';

        return DB::select($sql)[0]->count;
    }

    /**
     * Get a paginated list of the latest version of every atom that hasn't been deleted.
     *
     * @param string $query The user's search query
     * @param integer $productId Limit to this product
     * @param mixed[] $filters (optional) Filter the search with these key => value pairs
     * @param int $limit (optional) Max number of results per page
     * @param int $page (optional) The results page to retrieve
     *
     * @return string[] The IDs of all current atoms
     */
    public static function search($query, $productId, $filters = [], $limit = 10, $page = 1) {
        $unsortedCandidates = self::getSearchCandidates($query, $productId, $filters);
        $candidates = FuzzyRank::rank($unsortedCandidates, $query);
        $count = sizeof($candidates);
        $candidates = array_keys($candidates);
        $candidates = array_slice($candidates, ($page - 1) * $limit, $limit);       //handling paging outside of sql for better performance

        $query = self::whereIn('id', $candidates);

        return [
            'count' => $count,
            'atoms' => $query->get()
        ];
    }

    /**
     * Get a list of alpha titles and IDs of the latest version of every atom that hasn't been deleted.
     *
     * @param string $query The user's search query
     * @param integer $productId Limit to this product
     * @param mixed[] $filters (optional) Filter the search with these key => value pairs
     *
     * @return string[] The list of IDs
     */
    public static function getSearchCandidates($query, $productId, $filters = []) {
        $sanitizer = '/[^a-z0-9_.]/Si';
        $queryTitleConditions = [];
        $queryalphaTitleConditions = [];

        $query = trim(preg_replace($sanitizer, ' ', $query));
        $explodedQuery = preg_split('/\s+/', $query);
        foreach($explodedQuery as $queryPart) {
            $queryTitleConditions[] = [DB::raw('lower(title)'), 'like', '%' . $queryPart . '%'];
            $queryalphaTitleConditions[] = [DB::raw('lower(alpha_title)'), 'like', '%' . $queryPart . '%'];
        }

        //need to get the unranked list of candidates first
        $candidates = self::whereIn('id', function ($q) {
                    self::buildLatestIDQuery(null, $q);
                })
                ->where('product_id', '=', $productId)
                ->where(function ($query) use ($queryTitleConditions, $queryalphaTitleConditions) {
                    $query->where($queryTitleConditions)
                            ->orWhere($queryalphaTitleConditions);
                });

        self::_addFilters($candidates, $filters);

        return $candidates
                ->lists('alpha_title', 'id')
                ->all();
    }

    /**
     * Add filters to the query.
     *
     * @param object $query The query object to modify
     * @param mixed[] $filters The filters to add represented as key => value pairs
     */
    protected static function _addFilters($query, $filters) {
        $validFilters = ['status_id', 'molecule_code'];
        if($filters) {
            foreach($validFilters as $validFilter) {
                if(isset($filters[$validFilter])) {
                    $filterValue = $filters[$validFilter];
                    if($filterValue === '') {
                        $query->whereNull($validFilter);
                    }
                    else {
                        $query->where($validFilter, '=', $filterValue);
                    }
                }
            }
        }
    }

    /**
     * Get the latest version of the atom(s) regardless of whether or not it has been deleted.
     *
     * @param string|string[] $entityId The entityId(s) of the atom
     * @param integer $productId Limit to this product
     *
     * @return object|object[]|null The atom(s)
     */
    public static function findNewest($entityId, $productId) {
        if(is_array($entityId)) {      //plural
            return self::allForProduct($productId)
                    ->whereIn('id', function ($q) {
                        self::buildLatestIDQuery(null, $q);
                    })
                    ->whereIn('entity_id', $entityId)
                    ->orderBy('sort', 'ASC');
        }
        else {      //singular
            return self::allForProduct($productId)
                    ->withTrashed()
                    ->where('entity_id', '=', $entityId)
                    ->orderBy('id', 'desc')
                    ->first();
        }
    }

    /**
     * Get the latest version of an atom or null if it has been deleted.
     *
     * @param string $entityId The entityId of the atom
     * @param integer $productId Limit to this product
     *
     * @return mixed[]|null The atom
     */
    public static function findNewestIfNotDeleted($entityId, $productId) {
        $atom = self::withTrashed()
                ->where('entity_id', '=', $entityId)
                ->where('product_id', '=', $productId)
                ->orderBy('id', 'desc')
                ->first();

        return ($atom && $atom->trashed()) ? null : $atom;
    }

    /**
     * Get the oldest version of the atom(s) regardless of whether or not it has been deleted.
     *
     * @param string|string[] $entityId The entityId(s) of the atom
     * @param integer $productId Limit to this product
     *
     * @return object|object[]|null The atom(s)
     */
    public static function findOldest($entityId, $productId) {
            return self::allForProduct($productId)
                    ->withTrashed()
                    ->where('entity_id', '=', $entityId)
                    ->orderBy('id')
                    ->first();
    }

    /**
     * Add active assignments to the atom.
     *
     * @param integer $productId Limit to this product
     *
     * @return object This object
     */
    public function addAssignments($productId) {
        $this->assignments = self::getAssignments($this->entity_id, $productId)['assignments'];

        return $this;
    }

    /**
     * Add domain to the atom.
     *
     * @param integer $productId Limit to this product
     *
     * @return object This object
     */
    public function addDomains($productId) {
        preg_match_all('/<category[^>]*>(.*)<\/category>/Si', $this->xml, $matches);
        array_shift($matches[1]); //exclude main word's domain info since it has been stored in atom table
        $uniques = array_unique($matches[1]);
        sort($uniques);
        $subDomains = [];
        foreach ($uniques as $unique){
            //if ($unique !== $this->domain_code){
            if ($unique !== ' '){
                array_push($subDomains, $unique);
            }
            //}
        }
       $this->domains = $subDomains;

        return $this;
    }


    /**
     * Add filters to the query.
     *
     * @param object $query The query object to modify
     * @param mixed[] $filters The filters to add represented as key => value pairs
     */
    public function addCommentSuggestions($entityId) {

        $suggestedFigures = [];
        $suggestedFigureIds = Comment::getSuggestionIds($entityId);

        $this->suggestedFigures=$suggestedFigureIds;
        $this->xmlFigures = strpos($this->xml, 'type="figure"') !== false;  //return true if there's figure in xml
        return $this;
    }

    /**
     * Get active assignments for the given atom entityId.
     *
     * @param string $entityId The atom's entityId
     * @param integer $productId Limit to this product
     *
     * @return object[] The assignments
     */
    public static function getAssignments($entityId, $productId) {
        return (new Assignment)->getList(
            $productId,
            [
                'atom_entity_id' => $entityId
            ],
            null,
            [
                'column' => 'assignments.id',
                'direction' => 'asc'
            ]
        );
    }

    /**
     * Add comments to the atom.
     *
     * @return object This object
     */
    public function addComments() {
        $this->comments = Comment::getByAtomEntityId($this->entity_id);
        return $this;
    }

    /**
     * Perform workflow promotions.
     *
     * @param string[] $atomEntityIds The atoms' entityIds
     * @param mixed[] $promotion The promotion we're going to perform
     * @param integer $productId Limit to this product
     *
     * @return mixed[] The updated atoms with their assignments
     */
    public static function promote($atomEntityIds, $promotion, $productId) {
        $atomEntityIds = array_unique($atomEntityIds);      //no need to promote twice

        $locks = self::_locked($atomEntityIds, $productId);
        if($locks) {
            $molecule = current($locks);
            $moleculeTitle = $molecule ? $molecule->title : '';

            throw new \Exception('Chapter "' . $molecule->title . '" is locked, and cannot be modified at this time.');
        }

        foreach($promotion as $key => $value) {
            $promotion[$key] = $value === '' ? null : $value;
        }

        if(isset($promotion['user_id'])) {
            $userIds = User::allForCurrentProduct()->get()->pluck('user_id')->all();
            if(!in_array((int)$promotion['user_id'], $userIds)) {
                //throw new \Exception('Invalid user ID.');
            }
        }

        $atoms = [];
        foreach($atomEntityIds as $atomEntityId) {
            $atom = Atom::findNewest($atomEntityId, $productId);
            if(!$atom) {
                continue;       //skip atoms that don't exist or aren't in this product
            }

            Assignment::updateAssignments($atomEntityId, $promotion, $productId);

            //we might need to update the atom
            if(isset($promotion['status_id'])) {
                $atom->status_id = $promotion['status_id'];
                $atom->save([
                    'autoStatus'    => false
                ]);
            }

            $atom = $atom->addAssignments($productId)->toArray();
            $atoms[] = $atom;
        }

        return $atoms;
    }

    /**
     * Prepare and return the atom's XML.
     *
     * @return string
     */
    public function export() {
        $doctype = Product::find($this->product_id)->getDoctype();
        $xml = $doctype->assignXMLIds($this->xml, $this->entity_id);

        return $xml;
    }

    /**
     * Check if one or more atoms belong to a locked molecule.
     *
     * @param ?string|string[] $atomEntityIds The molecule code(s) to check
     * @param integer $productId The product's ID
     *
     * @return object[]
     */
    protected static function _locked($atomEntityIds, $productId) {
        $moleculeCodes = [];
        $atomEntityIds = is_array($atomEntityIds) ? $atomEntityIds : [$atomEntityIds];
        $atoms = self::whereIn('entity_id', $atomEntityIds)->get();
        foreach($atoms as $atom) {
            $moleculeCodes[] = $atom->molecule_code;
        }
        $moleculeCodes = array_unique($moleculeCodes);

        return Molecule::locked($moleculeCodes, $productId);
    }

    /**
     * Checks if there are any other atoms in this product that already use this title.
     *
     * @return boolean
     */
    protected function _isTitleInUse() {
        $count = self::allForProduct($this->product_id)
                ->where('entity_id', '<>', $this->entity_id)
                ->where('alpha_title', '=', $this->alpha_title)
                ->count();

        return $count > 0;
    }

    /**
     * Compare an atom to its previous version to see if it has any important changes.
     *
     * @return boolean Does it have significant changes?
     */
    protected function _hasSignificantChanges() {
        $currentVersion = $this->toArray();
        $previousVersion = ($this->entity_id && $this->product_id && self::findNewest($this->entity_id, $this->product_id)) ?
                self::findNewest($this->entity_id, $this->product_id)->toArray() :
                null;

        if(!$previousVersion) {
            return false;
        }

        foreach($currentVersion as $key => $value) {
            if(!in_array($key, self::$insignificantColumns) && $previousVersion[$key] != $value) {
                return true;
            }
        }

        return false;
    }
}