<?php

namespace App;

use App\AbstractDoctype;

class BookDoctype extends AbstractDoctype {
    protected $_config = [
        'validAtomRootElements' => ['group', 'monograph'],
        'validTitleElements' => ['group_title', 'mono_name'],
        'idPrefixes' => [
            'group' => 'g',
            'monograph' => 'm',
            'list' => 'l',
            'section' => 's',
            'para' => 'p',
            'table' => 't',
            'tgroup' => 'tg',
            'row' => 'r',
            'pill' => 'pl'
        ],
        'chapterElement' => [
            'elementXpath' => '//alpha',
            'keyAttributeName' => 'letter',
        ]
    ];
}
