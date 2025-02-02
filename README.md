
sudo mysql

CREATE DATABASE crypto_db;
CREATE USER 'admin'@'%' IDENTIFIED BY 'e433fdSDj3jF3_e';
GRANT ALL PRIVILEGES ON  crypto_db.* TO 'admin'@'%';
FLUSH PRIVILEGES;



php artisan orchid:admin admin admin@admin.com e433fdSDj2jF3_e


* * * * * cd /var/www/web-app && php artisan schedule:run >> /dev/null 2>&1


tmux new -s crypto_analyzer
tmux attach -t crypto_analyzer




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
command=php artisan queue:work --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=root
numprocs=2
redirect_stderr=true
stdout_logfile = /dev/fd/1
stdout_logfile_maxbytes=0
stderr_logfile = /dev/fd/2
stderr_logfile_maxbytes=0
redirect_stderr=true
stopwaitsecs=3600
priority = 6
stdout_logfile=/var/www/web-app/storage/logs/worker_cryptosasha.log


supervisorctl update
supervisorctl reload
supervisorctl restart all
