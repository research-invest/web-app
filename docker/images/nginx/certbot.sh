#!/bin/bash

# prod
certbot certonly --webroot -w /var/www/web-app/public -d selll.ru --agree-tos -n --email hello@selll.ru

cp /etc/letsencrypt/live/selll.ru/fullchain.pem /etc/nginx/ssl/selll.ru.crt
cp /etc/letsencrypt/live/selll.ru/privkey.pem /etc/nginx/ssl/selll.ru.key

nginx -g 'daemon off;'
