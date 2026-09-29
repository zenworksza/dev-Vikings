#!/bin/sh
set -e

cd /var/www/html

# `docker run vikings-site:prod <command>` bypasses the boot sequence.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

echo "Waiting for database..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "Database is up."

php artisan migrate --force

php artisan config:cache
php artisan route:cache
php artisan view:cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
