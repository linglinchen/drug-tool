<?php

namespace App;

use App\AbstractDoctype;

use App\Atom;

class DictionaryDoctype extends AbstractDoctype {
    protected $_config = [
        'validAtomRootElements' => ['main-entry'],
        'validTitleElements' => ['headw'],
        'idPrefixes' => [
            'main-entry' => 'me'
        ],
        'chapterElement' => [
            'elementXpath' => '//alpha',
            'keyAttributeName' => 'letter',
        ]
    ];

    public function beforeSave($atom) {
        $originalAtom = Atom::findNewestIfNotDeleted($atom->entity_id, $atom->product_id);
        $originalDomainCode = $originalAtom->domain_code;
        if($originalDomainCode != $atom->domain_code) {
            $replacement = '$1' . $atom->domain_code . '$3';
            $atom->xml = preg_replace('/(<category[^>]*>)(.*)(<\/category>)/Ssi', $replacement, $atom->xml);
        }
        else { 
            preg_match('/<category[^>]*>(.*)<\/category>/Si', $atom->xml, $matches);
            if($matches) {
                $atom->domain_code = trim($matches[1]);
            }
        }
        return true;
    }
}