#!/bin/bash
# Kept so `cd /var/www/vikings/www && ./deploy.sh` still works — the stack
# now spans both apps, so the real script lives at the repo root.
exec "$(dirname "$0")/../deploy.sh"
