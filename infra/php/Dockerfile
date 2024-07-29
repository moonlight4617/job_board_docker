FROM php:8.1-fpm-buster

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/composer

COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

RUN apt-get update && \
    apt-get -y install --no-install-recommends git unzip libzip-dev libicu-dev libonig-dev libpng-dev iputils-ping net-tools netcat-openbsd && \
    apt-get clean && \
    rm -rf /var/lib/apt/lists/* && \
    docker-php-ext-install intl pdo_mysql zip bcmath gd exif

COPY ./infra/php/php.ini /usr/local/etc/php/php.ini

COPY --chown=www-data:www-data ./src /data

WORKDIR /data
