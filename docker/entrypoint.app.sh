#!/bin/sh
set -e

# Готовим SQLite (по умолчанию) или ждём внешнюю БД.
if [ "$DB_CONNECTION" = "sqlite" ] || [ -z "$DB_CONNECTION" ]; then
    mkdir -p "$(dirname "${DB_DATABASE:-/var/www/database/database.sqlite}")"
    touch "${DB_DATABASE:-/var/www/database/database.sqlite}"
else
    echo "Waiting for database..."
    attempts=0
    until php -d display_errors=0 -r 'new PDO("mysql:host=".getenv("DB_HOST").";port=".getenv("DB_PORT").";dbname=".getenv("DB_DATABASE"), getenv("DB_USERNAME"), getenv("DB_PASSWORD"));' >/dev/null 2>&1; do
        attempts=$((attempts + 1))
        if [ "$attempts" -ge 30 ]; then
            echo "Database is not reachable after $attempts attempts, exiting." >&2
            exit 1
        fi
        sleep 2
    done
    echo "Database is ready."
fi

# Кэшируем конфиг и применяем миграции.
php artisan config:cache || true
php artisan migrate --force

# Порт из переменной окружения PORT (используется проверкой и деплоем).
PORT="${PORT:-8000}"
sed "s/{{PORT}}/$PORT/g" /etc/nginx/conf.d/default.conf.template > /etc/nginx/conf.d/default.conf

# Файлы, которые создают/кэшируют миграции, должны принадлежать www-data.
chown -R www-data:www-data storage bootstrap/cache database || true

# Запускаем php-fpm в фоне и nginx на переднем плане.
php-fpm -D
nginx -g 'daemon off;'
