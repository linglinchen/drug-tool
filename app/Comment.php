<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

use DB;

class Comment extends AppModel {
    use SoftDeletes;

    protected $table = 'comments';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at', 'deleted_at'];

    /**
     * Get comments for the given atom entityId(s).
     *
     * @param string|string[] $entityId The atom's entityId(s)
     * @param integer $productId The current product's id
     *
     * @return object[] The comments
     */
    protected static function getByAtomEntityId($entityId, $productId) {
        $comments = self::select('comments.*');
        if(is_array($entityId)) {
            $comments->whereIn('atom_entity_id', $entityId);
        }
        else {
            $comments->where('atom_entity_id', '=', $entityId);
        }

        $comments = $comments->join('atoms', 'comments.atom_entity_id', '=', 'atoms.entity_id')
                ->where('product_id', '=', $productId)
                ->groupBy('comments.id')
                ->orderBy('comments.id')
                ->get()
                ->toArray();

        return $comments;
    }

    /**
     * Get comments for the given product.
     *
     * @param integer $productId The current product's id
     *
     * @return object[] The comments
     */
    protected static function getByProductId($productId) {
        $comments = self::select('comments.*');

        $comments = $comments->join('atoms', 'comments.atom_entity_id', '=', 'atoms.entity_id')
                ->where('product_id', '=', $productId)
                //->where('comments.deleted_at' '=', null)
                ->groupBy('comments.id')
                ->orderBy('comments.id')
                ->get()
                ->toArray();

        return $comments;
    }

    /**
     * Get comments for the given comment Id.
     *
     * @param string|string[] $entityId The atom's entityId(s)
     * @param integer $productId The current product's id
     *
     * @return object[] The comments
     */
    protected static function getByCommentId($commentId) {
        $commentRecord = self::select('comments.*')->where('id', '=', $commentId);
        return $commentRecord;
    }

    /**
     * Add a summary of comments to an atom collection.
     *
     * @param object $atoms The atom collection
     * @param integer $productId The current product's id
     *
     * @return object This object
     */
    public static function addSummaries($atoms, $productId) {
        $groupedComments = [];
        $commentSummaries = [];
        $comments = self::getByProductId($productId);

        foreach($comments as $comment) {
            if(!isset($groupedComments[$comment['atom_entity_id']])) {
                $groupedComments[$comment['atom_entity_id']] = [];
            }

            $groupedComments[$comment['atom_entity_id']][] = $comment;
        }

        foreach($groupedComments as $entityId => $group) {
            $commentSummaries[$entityId] = [
                    'count' => sizeof($group),
                    'last_comment' => [
                        'date' => sizeof($group) ? $group[0]['created_at'] : null,
                        'user_id' => sizeof($group) ? $group[0]['user_id'] : null
                    ]
                ];
        }

        foreach($atoms as $atom) {
            $atom->comment_summary = isset($commentSummaries[$atom->entity_id]) ? $commentSummaries[$atom->entity_id] : null;
        }
    }

    /**
     * get a list of comments arrays
     *
     * @param integer
     *
     * @return object The constructed query object
     */
    public static function getSuggestionIds($entityId) {
        //select the Comment id and the figure src info for records that have figure queries with uploaded images.
        //full credit could be empty, so cannot use unnest
        $sql = 'select id, text,
            unnest(xpath(\'//query[@type="figure"]/suggestion/text()\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', text, \'</root>\'))::xml)) as reviewstatus,
            unnest(xpath(\'//query[@type="figure"]/availability/text()\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', text, \'</root>\'))::xml)) as availability,
            unnest(xpath(\'//query[@type="figure"]/component[@type="figure"]/ce_caption/text()\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', text, \'</root>\'))::xml)) as caption,
            unnest(xpath(\'//query[@type="figure"]/component[@type="figure"]/credit/text()\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', text, \'</root>\'))::xml)) as credit,
            array_to_string(xpath(\'//query[@type="figure"]/component[@type="figure"]/fullcredit/text()\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', text, \'</root>\'))::xml), \'\') as fullcredit,
            unnest(xpath(\'//query[@type="figure"]/component[@type="figure"]/file/@src\', XMLPARSE(DOCUMENT CONCAT(\'<root>\', text, \'</root>\'))::xml)) as figurefile from comments
            where atom_entity_id=\''. $entityId .'\'';

        $idArray= DB::select($sql);
        $idArray = json_decode(json_encode($idArray), true);
        return $idArray;
    }

}