<?php

namespace App;

use App\AppModel;

class Status extends AppModel {
    protected $table = 'statuses';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
}
