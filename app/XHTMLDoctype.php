<?php

namespace App;

use App\AbstractDoctype;

use App\Atom;

class XHTMLDoctype extends AbstractDoctype {
    protected $_config = [
		'validAtomRootElements' => ['html'],
		'ignoreAtomRootElements' => [],
        'validTitleElements' => ['title'],
        'idPrefixes' => [
			'html' => 'html.',
			'body' => 'body.',
			'div' => 'div.',
			'h1' => 'h1.',
			'h2' => 'h2.',
			'h3' => 'h3.',
			'h4' => 'h4.',
			'h5' => 'h5.',
			'ol' => 'ol.',
			'ul' => 'ul.',
			'li' => 'li.',
			'p' => 'p.',
			'span' => 'span.',
			'a' => 'a.',
			'img' => 'img.',
			'table' => 'table.',
			'tr' => 'tr.',
			'th' => 'th.',
			'td' => 'td.',
        ],
        'chapterElement' => [
            'elementXpath' => '//xhtml:root',
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