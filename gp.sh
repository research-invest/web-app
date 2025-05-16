#!/bin/bash
git pull

php artisan migrate --force
php artisan optimize:clear
php artisan queue:restart

SCRIPT_DIR=$(dirname "$(readlink -f "$0")")

chown -R www-data:www-data "$SCRIPT_DIR/storage"
chown -R www-data:www-data "$SCRIPT_DIR/bootstrap/cache"
chmod -R 775 "$SCRIPT_DIR/storage"
chmod -R 775 "$SCRIPT_DIR/bootstrap/cache"


#supervisorctl restart worker_cryptosasha:*


