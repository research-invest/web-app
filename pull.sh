#!/bin/bash
git pull
chown -R www-data:www-data /var/www/web-app/storage
chown -R www-data:www-data /var/www/web-app/bootstrap/cache
chmod -R 775 /var/www/web-app/storage
chmod -R 775 /var/www/web-app/bootstrap/cache

php artisan migrate
php artisan optimize
