<?php

namespace App;

use App\AbstractDoctype;

class DictionaryDoctype extends AbstractDoctype {
    protected $_config = [
        'validAtomRootElements' => ['main-entry'],
        'validTitleElements' => ['headw'],
        'idPrefixes' => [],
        'chapterElement' => [
            'elementXpath' => '//alpha',
            'keyAttributeName' => 'letter',
        ]
    ];
}
