<?php

namespace App;

use App\AppModel;

class Product extends AppModel {
    protected $table = 'products';
    protected $guarded = ['id'];
}
