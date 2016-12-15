<?php

namespace App;

use App\AbstractDoctype;

class DictionaryDoctype extends AbstractDoctype {
    protected $_config = [
        'validRootElements' => ['main-entry'],
        'validTitleElements' => ['headw'],
        'idPrefixes' => []
    ];
}
