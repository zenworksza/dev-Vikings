#!/bin/bash
# Run on the prod server, from /var/www/vikings/www (see README's Deploy
# convention). Pulls latest, rebuilds the app image, brings the stack up.
set -euo pipefail

cd "$(dirname "$0")"

git -C .. pull

docker compose -f compose.prod.yaml build app
docker compose -f compose.prod.yaml up -d

echo "Deployed. Tailing app logs (Ctrl+C to stop):"
docker compose -f compose.prod.yaml logs -f --tail=50 app
