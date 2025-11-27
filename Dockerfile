FROM php:8.1-fpm

# Install PHP extensions and Nginx
RUN apt-get update && apt-get install -y \
    nginx \
    supervisor \
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

# Supervisor config to run php-fpm + nginx
RUN mkdir -p /etc/supervisor/conf.d
COPY supervisord.conf /etc/supervisor/conf.d/supervisord.conf

# Permissions
RUN chown -R www-data:www-data /var/www/html

# Expose port 10000 for Render
EXPOSE 10000

# Start supervisor (manages both php-fpm and nginx)
CMD ["/usr/bin/supervisord", "-n", "-c", "/etc/supervisor/conf.d/supervisord.conf"]