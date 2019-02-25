<?php

namespace App;

use App\AbstractDoctype;

use App\Atom;

class BookDoctype extends AbstractDoctype {
    protected $_config = [
        'validAtomRootElements' => ['chapter'],
        //'ignoreAtomRootElements' => ['info', 'ce:label'],
        'validTitleElements' => ['ce:title'],
        'idPrefixes' => [
            'chapter' => 'c'
        ],
        'chapterElement' => [
            'elementXpath' => '//molecule',
            'keyAttributeName' => 'code',
        ]
    ];

    public function beforeSave($atom) {
         $originalAtom = Atom::findNewestIfNotDeleted($atom->entity_id, $atom->product_id);
         if ($originalAtom){ // for existing atom

        } //if it's new atom, just return true
        return true;
    }
}