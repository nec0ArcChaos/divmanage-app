#!/bin/sh
set -e

cd /var/www/html

echo "Running migrations..."
php artisan migrate --force

echo "Starting services..."
exec /usr/bin/supervisord -c /etc/supervisor/conf.d/supervisord.conf
