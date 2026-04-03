FROM php:8.1-fpm

# Install system dependencies first
RUN apt-get update --fix-missing && \
    apt-get install -y nginx supervisor libzip-dev unzip zip git libpng-dev \
    libjpeg-dev libfreetype6-dev libonig-dev libxml2-dev build-essential pkg-config && \
    apt-get clean && rm -rf /var/lib/apt/lists/*
    
# Install PHP extensions (configure gd first)
RUN docker-php-ext-configure gd --with-freetype --with-jpeg && \
    docker-php-ext-install gd zip mysqli pdo pdo_mysql mbstring xml fileinfo ctype

# Install Redis via PECL
RUN pecl install -o -f redis && \
    docker-php-ext-enable redis

# Copy Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Copy app code
COPY . /var/www/html
WORKDIR /var/www/html

# Install PHP dependencies
RUN COMPOSER_MEMORY_LIMIT=-1 composer install --no-dev --optimize-autoloader

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
