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
     *
     * @return object[] The comments
     */
    protected static function getByAtomEntityId($entityId) {
        if(is_array($entityId)) {
            $comments = self::whereIn('atom_entity_id', $entityId);
        }
        else {
            $comments = self::where('atom_entity_id', '=', $entityId);
        }

        $comments = $comments->get()
                ->toArray();

        return $comments;
    }

    /**
     * Add a summary of comments to an atom collection.
     *
     * @param object $atoms The atom collection
     *
     * @return object This object
     */
    public static function addSummaries($atoms) {
        $groupedComments = [];
        $commentSummaries = [];
        $entityIds = array_unique($atoms->pluck('entity_id')->toArray());
        $comments = self::getByAtomEntityId($entityIds);

        foreach($comments as $comment) {
            if(!isset($groupedComments[$comment['atom_entity_id']])) {
                $groupedComments[$comment['atom_entity_id']] = [];
            }

            $groupedComments[$comment['atom_entity_id']][] = $comment;
        }

        foreach($groupedComments as $entityId => $group) {
            $commentSummaries[$entityId] = [
                    'count' => sizeof($group),
                    'lastComment' => [
                        'date' => sizeof($group) ? $group[0]['created_at'] : null,
                        'user_id' => sizeof($group) ? $group[0]['user_id'] : null
                    ]
                ];
        }

        foreach($atoms as $atom) {
            $atom->commentSummary = isset($commentSummaries[$atom->entityId]) ? $commentSummaries[$atom->entityId] : null;
        }
    }
}
