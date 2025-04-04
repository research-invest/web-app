
sudo mysql

CREATE DATABASE crypto_db;
CREATE USER 'admin'@'%' IDENTIFIED BY 'e433fdSDj3jF3_e';
GRANT ALL PRIVILEGES ON  crypto_db.* TO 'admin'@'%';
FLUSH PRIVILEGES;

https://mexcdevelop.github.io/apidocs/spot_v3_en/#introduction
https://www.gate.io/docs/developers/apiv4/#funding-account-list


https://github.com/zhouaini528/exchanges-php
https://github.com/zhouaini528/mxc-php
https://github.com/zhouaini528/bybit-php
https://github.com/zhouaini528/kucoin-php
https://github.com/zhouaini528/gate-php


php artisan orchid:admin admin admin@admin.com e433fdSDj2jF3_e


* * * * * cd /var/www/web-app && php artisan schedule:run >> /dev/null 2>&1


tmux new -s crypto_analyzer
tmux attach -t crypto_analyzer

prod
composer install --no-interaction --no-dev



sudo apt install supervisor
cd /etc/supervisor/conf.d/
nano /etc/supervisor/conf.d/cryptosasha.conf


[supervisord]
logfile=/var/log/supervisor/supervisord.log
nodaemon = false
pidfile = /run/supervisord.pid

[rpcinterface:supervisor]
supervisor.rpcinterface_factory = supervisor.rpcinterface:make_main_rpcinterface

[supervisorctl]
serverurl=unix:///tmp/supervisor.sock

[program:worker_cryptosasha]
directory=/var/www/web-app
process_name=%(program_name)s_%(process_num)02d
command=php artisan queue:work --sleep=1 --tries=3 --timeout=900
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=root
numprocs=4
redirect_stderr=true
stdout_logfile=/var/www/web-app/storage/logs/worker_cryptosasha.log
stdout_logfile_maxbytes=0
stopwaitsecs=10
priority=6


supervisorctl update
supervisorctl reload
supervisorctl restart all

sudo supervisorctl reread && supervisorctl update
sudo supervisorctl restart worker_cryptosasha:*
