<?php

namespace App;

use App\AppModel;

class Task extends AppModel {
    protected $table = 'tasks';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
}
