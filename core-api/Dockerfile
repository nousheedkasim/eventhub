FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite for Laravel routing
RUN a2enmod rewrite

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer manifests first (best-effort for caching)
COPY composer.json composer.lock ./
RUN if [ -f composer.json ]; then composer install --no-interaction --prefer-dist --no-dev; fi

# Copy the rest of the application
COPY . .

# Permissions for storage
RUN chown -R www-data:www-data storage bootstrap/cache || true

EXPOSE 8000

# Laravel 11 entry (Apache)
# Document root is already /var/www/html/public

