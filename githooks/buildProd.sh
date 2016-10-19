#!/bin/bash

scl enable rh-php56 -- php artisan clear-compiled
scl enable rh-php56 -- ~/composer.phar dump-autoload
scl enable rh-php56 -- ~/composer.phar install
scl enable rh-php56 -- php artisan migrate