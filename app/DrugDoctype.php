<?php

namespace App;

use App\AbstractDoctype;

class DrugDoctype extends AbstractDoctype {
    protected $_config = [
        'validRootElements' => ['group', 'monograph'],
        'validTitleElements' => ['group_title', 'mono_name'],
    ];
}
