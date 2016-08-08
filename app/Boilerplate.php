<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Boilerplate extends Model
{
    protected $table = 'boilerplates';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
}
