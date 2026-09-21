#!/bin/sh
set -e

cd /var/www/html

# Allow `docker run vikings-app:prod <command>` (e.g. one-off artisan
# commands like key:generate) to bypass the migrate/cache boot sequence
# below and just run that command directly.
if [ "$#" -gt 0 ]; then
    exec "$@"
fi

echo "Waiting for database..."
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" 2>/dev/null; do
    sleep 2
done
echo "Database is up."

php artisan migrate --force

# Settings values are cached (SETTINGS_CACHE_ENABLED=true) — see Plan.md's
# "settings cache gotcha": stale values throw after any settings migration,
# so always clear on deploy rather than only when a settings migration ran.
php artisan settings:clear-cache || true

php artisan storage:link || true

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

exec supervisord -c /etc/supervisor/conf.d/supervisord.conf
