FROM php:8.2-apache

# System dependencies
RUN apt-get update && apt-get install -y \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip pdo pdo_mysql \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /var/lib/apt/lists/*

# Enable mod_rewrite for Laravel routing
RUN a2enmod rewrite

# Point DocumentRoot to Laravel public directory
RUN sed -i 's|/var/www/html|/var/www/html/public|g' /etc/apache2/sites-enabled/000-default.conf

# Allow .htaccess overrides
RUN sed -i '/<Directory \/var\/www\/>/,/<\/Directory>/ s/AllowOverride None/AllowOverride All/' /etc/apache2/apache2.conf

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer manifests first (best-effort for caching)
COPY composer.json composer.lock ./
# Install dependencies without running Laravel scripts during image build.
# Laravel's post-autoload-dump currently calls `artisan`, which isn't available until after COPY . .
RUN if [ -f composer.json ]; then \
    composer install --no-interaction --prefer-dist --no-dev --no-scripts; \
fi

# Copy the rest of the application
COPY . .

# Permissions for storage
RUN chown -R www-data:www-data storage bootstrap/cache || true

EXPOSE 80