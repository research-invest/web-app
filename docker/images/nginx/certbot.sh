#!/bin/bash

# for dev
certbot certonly --webroot -w /var/www/app/public -d flyki.axiomica.io --agree-tos -n --email hello@exchanger.axiomica.io

cp /etc/letsencrypt/live/exchanger.axiomica.io/fullchain.pem /etc/nginx/ssl/exchanger.axiomica.io.crt
cp /etc/letsencrypt/live/exchanger.axiomica.io/privkey.pem /etc/nginx/ssl/exchanger.axiomica.io.key

nginx -g 'daemon off;'


# prod
certbot certonly --webroot -w /var/www/flyki-landing/public -d flyki.app --agree-tos -n --email hello@flyki.app

cp /etc/letsencrypt/live/flyki.app/fullchain.pem /etc/nginx/ssl/flyki.app.crt
cp /etc/letsencrypt/live/flyki.app/privkey.pem /etc/nginx/ssl/flyki.app.key
