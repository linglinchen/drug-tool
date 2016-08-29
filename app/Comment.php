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
            $comments = self::whereIn('atomEntityId', $entityId);
        }
        else {
            $comments = self::where('atomEntityId', '=', $entityId);
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
        $entityIds = array_unique($atoms->pluck('entityId')->toArray());
        $comments = self::getByAtomEntityId($entityIds);

        foreach($comments as $comment) {
            if(!isset($groupedComments[$comment['atomEntityId']])) {
                $groupedComments[$comment['atomEntityId']] = [];
            }

            $groupedComments[$comment['atomEntityId']][] = $comment;
        }

        foreach($groupedComments as $entityId => $group) {
            $commentSummaries[$entityId] = [
                    'count' => sizeof($group),
                    'lastComment' => [
                        'date' => sizeof($group) ? $group[0]['created_at'] : null,
                        'userId' => sizeof($group) ? $group[0]['userId'] : null
                    ]
                ];
        }

        foreach($atoms as $atom) {
            $atom->commentSummary = isset($commentSummaries[$atom->entityId]) ? $commentSummaries[$atom->entityId] : null;
        }
    }
}
