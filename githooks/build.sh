#!/bin/bash

php artisan clear-compiled
composer dump-autoload
composer install
php artisan migrate