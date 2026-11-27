#!/usr/bin/env bash
# Update the live site after pushing to GitHub. Run on the server, inside the app folder:
#   bash deploy.sh
set -euo pipefail

echo "→ Maintenance mode on"
php artisan down --retry=15 || true

echo "→ Pulling latest code"
git pull --ff-only origin main

echo "→ Installing PHP packages"
COMPOSER=$(command -v composer2 || command -v composer)
$COMPOSER install --no-dev --optimize-autoloader --no-interaction

echo "→ Updating database"
php artisan migrate --force
php artisan db:seed --class=CategorySeeder --force

echo "→ Caching config, routes and views"
php artisan optimize:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan storage:link 2>/dev/null || true

echo "→ Maintenance mode off"
php artisan up

echo "✓ Deployed $(git log --oneline -1)"
