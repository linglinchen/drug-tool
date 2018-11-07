<?php


namespace App;

use App\AppModel;

class LoginHistory extends AppModel {
    public $timestamps = false;
    protected $table = 'login_history';
    protected $guarded = ['id'];
    protected $dates = ['login_time'];
}