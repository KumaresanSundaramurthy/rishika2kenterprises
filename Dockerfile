FROM php:8.1.0-fpm

RUN pecl install -o -f redis \
&&  rm -rf /tmp/pear \
&&  docker-php-ext-enable redis

RUN apt-get update && apt-get install -y \
    libzip-dev \
    unzip \
    zip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip mysqli fileinfo

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

COPY ./ /var/www
WORKDIR /var/www

RUN composer install --no-interaction --prefer-dist --optimize-autoloader

CMD ["php-fpm"]