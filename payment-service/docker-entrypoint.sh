#!/bin/bash
set -e

cd /var/www/html

# Ensure SQLite database exists
mkdir -p database
touch database/database.sqlite

# Run migrations
php artisan migrate --force 2>&1 || true

# Start queue worker in the background
php artisan queue:work --sleep=3 --tries=3 --max-time=3600 &

# Start Apache in the foreground
exec apache2-foreground
