#!/bin/bash

php artisan clear-compiled
~/composer.phar dump-autoload
~/composer.phar install
php artisan migrate
sudo systemctl reload httpd.service