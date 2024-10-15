
sudo mysql

CREATE DATABASE crypto_db;
CREATE USER 'admin'@'%' IDENTIFIED BY 'e433fdSDj3jF3_e';
GRANT ALL PRIVILEGES ON  crypto_db.* TO 'admin'@'%';
FLUSH PRIVILEGES;



php artisan orchid:admin admin admin@admin.com e433fdSDj2jF3_e
