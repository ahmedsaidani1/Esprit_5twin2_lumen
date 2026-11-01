#!/bin/sh
# Démarrage du conteneur. CONTAINER_ROLE choisit ce qu'il fait :
#   app        -> migrations, mise en cache, puis Apache (le site)
#   queue      -> traite la file d'attente (e-mails, notifications)
#   scheduler  -> lance les tâches planifiées (expiration des offres, entraînement IA)
set -e

cd /var/www/html

echo "En attente de la base de données ${DB_HOST}:${DB_PORT:-3306}..."
i=0
until php -r 'try { new PDO("mysql:host=".getenv("DB_HOST").";port=".(getenv("DB_PORT") ?: 3306), getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); } catch (Exception $e) { exit(1); }'; do
    i=$((i + 1))
    [ "$i" -ge 60 ] && echo "Base de données injoignable." && exit 1
    sleep 2
done

case "${CONTAINER_ROLE:-app}" in
    app)
        php artisan migrate --force
        if [ "${SEED_DEMO:-false}" = "true" ] && [ "$(php artisan tinker --execute='echo App\Models\User::count();')" = "0" ]; then
            echo "Base vide : chargement des données de démonstration."
            php artisan db:seed --class=DemoSeeder --force
        fi
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
        chown -R www-data:www-data storage bootstrap/cache
        exec apache2-foreground
        ;;
    queue)
        exec php artisan queue:work --sleep=3 --tries=3 --max-time=3600
        ;;
    scheduler)
        exec php artisan schedule:work
        ;;
    *)
        exec "$@"
        ;;
esac
