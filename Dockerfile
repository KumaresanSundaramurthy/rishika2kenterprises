FROM php:8.1.0-fpm

COPY ./ /app
WORKDIR /var/www

RUN pecl install -o -f redis \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis

RUN docker-php-ext-install mysqli

CMD ["php-fpm"]