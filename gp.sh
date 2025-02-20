#!/bin/bash
git pull

php artisan migrate --force
php artisan optimize
php artisan queue:restart

chown -R www-data:www-data /var/www/web-app/storage
chown -R www-data:www-data /var/www/web-app/bootstrap/cache
chmod -R 775 /var/www/web-app/storage
chmod -R 775 /var/www/web-app/bootstrap/cache


#supervisorctl restart worker_cryptosasha:*


