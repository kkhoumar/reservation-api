#!/bin/sh
set -e

# Caches Laravel (dans chaque conteneur, car bootstrap/cache n'est pas partagé)
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache

# Migrations uniquement sur le conteneur "app" (RUN_MIGRATIONS=true)
if [ "${RUN_MIGRATIONS:-false}" = "true" ]; then
    php artisan migrate --force
fi

exec "$@"
