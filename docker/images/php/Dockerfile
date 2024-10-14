FROM php:8.3-fpm

RUN mkdir -p /var/www

RUN apt-get update && apt-get install -y \
        nano \
        curl \
        libpq-dev \
        libicu-dev \
        libzip-dev \
        libxml2-dev \
        zip \
        libmagickwand-dev \
        git \
        software-properties-common \
        npm \
        supervisor && \
    docker-php-ext-install -j$(nproc) iconv pdo pdo_mysql bcmath intl zip soap && \
    apt-get clean && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN npm install npm@latest -g && \
    npm install n -g && \
    n latest

#RUN pecl install imagick \
#    && docker-php-ext-enable imagick


ARG IMAGICK_VERSION=3.7.0
#https://gist.github.com/Wirone/d5c794b4fef0203146a27687e80588a6
# Imagick is installed from the archive because regular installation fails
# See: https://github.com/Imagick/imagick/issues/643#issuecomment-1834361716
RUN curl -L -o /tmp/imagick.tar.gz https://github.com/Imagick/imagick/archive/refs/tags/${IMAGICK_VERSION}.tar.gz \
    && tar --strip-components=1 -xf /tmp/imagick.tar.gz \
    && phpize \
    && ./configure \
    && make \
    && make install \
    && echo "extension=imagick.so" > /usr/local/etc/php/conf.d/ext-imagick.ini \
    && rm -rf /tmp/*
    # <<< End of Imagick installation

RUN mkdir -p /var/log/supervisor

RUN mkdir -p /var/run/supervisor && \
    chmod 777 /var/run/supervisor

RUN pecl install -o -f redis \
    &&  rm -rf /tmp/pear \
    &&  docker-php-ext-enable redis

WORKDIR /var/www/app

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

CMD ["sh", "-c", "php-fpm & /usr/bin/supervisord -n -c /etc/supervisor/supervisord.conf"]
