#!/bin/sh
# Démarrage sur Render (offre gratuite) : un seul conteneur pour tout.
set -e
cd /var/www/html

# Adresse publique donnée par Render
export APP_URL="${APP_URL:-$RENDER_EXTERNAL_URL}"

# Render génère une valeur aléatoire ; Laravel veut une clé de 32 octets.
# On en tire toujours la même clé (sinon les sessions sauteraient à chaque démarrage).
if [ -n "$APP_KEY" ] && [ "${APP_KEY#base64:}" = "$APP_KEY" ]; then
    APP_KEY="base64:$(php -r 'echo base64_encode(hash("sha256", getenv("APP_KEY"), true));')"
    export APP_KEY
fi

php artisan config:cache
php artisan route:cache || php artisan route:clear
php artisan view:cache
php artisan event:cache

php artisan migrate --force

# Compte Super Admin (numéro et mot de passe collés dans Render, jamais dans le code)
if [ -n "$SUPERADMIN_PHONE" ] && [ -n "$SUPERADMIN_PASSWORD" ]; then
    php artisan app:create-super-admin "$SUPERADMIN_PHONE" --first="${SUPERADMIN_FIRST:-Super}" --last="${SUPERADMIN_LAST:-Admin}" --password="$SUPERADMIN_PASSWORD" || true
fi

# Comptes de démo : jamais le mot de passe « password » sur le serveur en ligne
php artisan app:secure-demo || true

# Maquis de démonstration (ne crée rien s'il existe déjà ; demande DEMO_PASSWORD)
if [ "${SEED_DEMO:-false}" = "true" ]; then
    php artisan db:seed --class=DemoSeeder --force || true
fi

chown -R www-data:www-data storage bootstrap/cache

# Tâches planifiées (rappels d'abonnement) : le conteneur reste éveillé grâce à cron-job.org
su -s /bin/sh www-data -c "php artisan schedule:work" &

sed "s/__PORT__/${PORT:-10000}/" docker/render/nginx.conf.template > /etc/nginx/http.d/default.conf

php-fpm -D
exec nginx -g 'daemon off;'
