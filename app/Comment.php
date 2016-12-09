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
        $entityIds = array_unique($atoms->pluck('entity_id')->toArray());
        $comments = self::getByAtomEntityId($entityIds, $productId);

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
}
