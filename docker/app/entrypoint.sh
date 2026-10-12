#!/bin/sh
set -e

# vendor/ lives in a named volume, so it starts empty on a fresh stack.
if [ ! -f /app/vendor/autoload.php ]; then
    echo "[orbita] vendor/ vacio, instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist --no-progress
fi

if [ ! -f /app/.env ]; then
    echo "[orbita] .env ausente, copiando desde .env.example..."
    cp /app/.env.example /app/.env
    php artisan key:generate --force
fi

# Postgres is healthy before this runs (see depends_on in compose.yaml).
php artisan migrate --force

exec "$@"
