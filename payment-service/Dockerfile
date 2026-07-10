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

# Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Copy composer manifests first (best-effort for caching)
COPY composer.json composer.lock ./
# Install dependencies without running Laravel scripts during image build
RUN if [ -f composer.json ]; then \
    composer install --no-interaction --prefer-dist --no-dev --no-scripts; \
    mkdir -p database && touch database/database.sqlite; \
fi

# Copy the rest of the application
COPY . .

# Permissions for storage
RUN chown -R www-data:www-data storage bootstrap/cache || true

# Change Apache port to 8001
RUN sed -i 's/:80/:8001/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf \
    && sed -i 's/Listen 80/Listen 8001/g' /etc/apache2/ports.conf

EXPOSE 8001
