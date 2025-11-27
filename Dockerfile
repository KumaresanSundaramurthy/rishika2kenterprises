FROM php:8.1-fpm

# Install PHP extensions
RUN apt-get update && apt-get install -y \
    nginx \
    libzip-dev \
    unzip \
    zip \
    git \
    libpng-dev \
    libjpeg-dev \
    libfreetype6-dev \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install gd zip mysqli fileinfo \
    && pecl install redis && docker-php-ext-enable redis

# Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app code
COPY . /var/www/html
WORKDIR /var/www/html

# Copy nginx config
COPY nginx/default.conf /etc/nginx/conf.d/default.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 10000 for Render
EXPOSE 10000

# Start both services
CMD service php8.1-fpm start && nginx -g 'daemon off;'
