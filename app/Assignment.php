<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Assignment extends Model
{
    protected $table = 'assignments';
    protected $guarded = ['id'];
    protected $dates = ['created_at', 'updated_at'];
}
