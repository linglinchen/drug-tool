<?php

namespace App;

use App\AbstractDoctype;

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
}
