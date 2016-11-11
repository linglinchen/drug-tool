<?php

namespace App\Providers;

use \Illuminate\Auth\EloquentUserProvider;
use Illuminate\Support\ServiceProvider;

use App\UserProduct;

class ApiUserProvider extends EloquentUserProvider {}