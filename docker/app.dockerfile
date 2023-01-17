FROM php:8.1-fpm

RUN apt-get update && \
     apt-get install -y zip
RUN docker-php-ext-install pdo_mysql mysqli calendar

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www
