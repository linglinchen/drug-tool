<?php

namespace App;

use App\AppModel;

class Boilerplate extends AppModel {
    protected $table = 'boilerplates';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
}
