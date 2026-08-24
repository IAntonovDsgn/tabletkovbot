FROM php:8.4-fpm

RUN apt-get update && apt-get install -y wget curl git unzip
RUN docker-php-ext-install pdo pdo_mysql pcntl sockets
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

WORKDIR /var/www/tabletkovbot/

CMD ["php-fpm"]
