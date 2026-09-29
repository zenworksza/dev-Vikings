#!/bin/bash
# Run on the prod server from /var/www/vikings (or via www/deploy.sh).
# git pull -> bootstrap the site's .env + database on first run -> rebuild
# both images -> bring the stack up.
set -euo pipefail

cd "$(dirname "$0")"

# Wrapped in a block so bash has read the whole script before `git pull`
# can replace this file underneath it.
{
    git pull

    [ -f www/.env ] || { echo "www/.env is missing — create it from www/.env.production.example" >&2; exit 1; }

    COMPOSE="docker compose --env-file www/.env -f compose.prod.yaml"

    # First run: create the site's .env with a fresh APP_KEY and DB password.
    if [ ! -f site/.env ]; then
        echo "Creating site/.env"
        app_key="base64:$(openssl rand -base64 32)"
        db_pw="$(openssl rand -hex 24)"
        sed -e "s#__APP_KEY__#${app_key}#" -e "s#__DB_PASSWORD__#${db_pw}#" \
            site/.env.production.example > site/.env
    fi

    $COMPOSE build portal site

    # Start the database first so the site's database/user can be created
    # (idempotent — safe on every deploy; also re-syncs the password).
    $COMPOSE up -d mysql
    root_pw="$(grep -E '^DB_PASSWORD=' www/.env | head -1 | cut -d= -f2- | sed 's/[[:space:]]*#.*$//')"
    site_db="$(grep -E '^DB_DATABASE=' site/.env | cut -d= -f2-)"
    site_user="$(grep -E '^DB_USERNAME=' site/.env | cut -d= -f2-)"
    site_pw="$(grep -E '^DB_PASSWORD=' site/.env | cut -d= -f2-)"

    # Wait for a real authenticated login, not `mysqladmin ping` — on a fresh
    # volume ping succeeds against the temporary init server before the root
    # password is set.
    until $COMPOSE exec -T mysql mysql -uroot -p"$root_pw" -e 'SELECT 1' >/dev/null 2>&1; do
        echo "Waiting for MySQL..."
        sleep 3
    done
    sleep 2

    $COMPOSE exec -T mysql mysql -uroot -p"$root_pw" -e "
        CREATE DATABASE IF NOT EXISTS \`${site_db}\`;
        CREATE USER IF NOT EXISTS '${site_user}'@'%' IDENTIFIED BY '${site_pw}';
        ALTER USER '${site_user}'@'%' IDENTIFIED BY '${site_pw}';
        GRANT ALL PRIVILEGES ON \`${site_db}\`.* TO '${site_user}'@'%';
        FLUSH PRIVILEGES;"

    # --remove-orphans clears the old single-app stack's `app` container
    # (renamed `portal`), which would otherwise keep port 80.
    $COMPOSE down --remove-orphans
    $COMPOSE up -d

    echo "Deployed. Tailing logs (Ctrl+C to stop):"
    $COMPOSE logs -f --tail=30 proxy portal site
    exit
}
