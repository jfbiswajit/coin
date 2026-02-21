#!/bin/sh
set -e

cd /var/www/html

php artisan migrate --force

php artisan package:discover --ansi
php artisan config:cache
php artisan route:cache
php artisan view:cache

cp -r /var/www/html/public/. /var/www/html/public-volume/

exec php-fpm
