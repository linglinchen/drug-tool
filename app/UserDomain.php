<?php

namespace App;

use App\AppModel;

class Task extends AppModel {
    protected $table = 'user_domains';
    protected $guarded = ['id'];
}
