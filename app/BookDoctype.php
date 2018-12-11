<?php

namespace App;

use App\AbstractDoctype;

use App\Atom;

class BookDoctype extends AbstractDoctype {
    protected $_config = [
        'validAtomRootElements' => ['chapter'],
        'ignoreAtomRootElements' => ['bk__info', 'ce__label', 'ce__title'],
        'validTitleElements' => ['ce__section-title'],
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