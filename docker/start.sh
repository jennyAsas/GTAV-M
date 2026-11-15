#!/bin/sh
set -e

# Render supplies PORT; default to 10000.
PORT="${PORT:-10000}"
sed -i "s/Listen 80/Listen ${PORT}/" /etc/apache2/ports.conf
sed -i "s/:80>/:${PORT}>/" /etc/apache2/sites-available/000-default.conf

if [ -z "$APP_KEY" ]; then
    echo "APP_KEY is not set. Generate one with 'php artisan key:generate --show' and set it in Render." >&2
    exit 1
fi

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan migrate --force
php artisan db:seed --force

exec apache2-foreground
