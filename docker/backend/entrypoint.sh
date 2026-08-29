#!/bin/sh
set -e

# Ждём готовности базы данных (защита от стартовой гонки с mysql).
# Проверка — прямой PDO-коннект (db:show требует intl-расширение).
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

# Кэшируем конфиг (необходим APP_KEY из окружения).
php artisan config:cache || true

# Применяем миграции перед стартом.
php artisan migrate --force

# Миграции и config:cache создают файлы от root; php-fpm работает под www-data.
chown -R www-data:www-data storage bootstrap/cache

# Запускаем php-fpm в фоне и nginx на переднем плане.
php-fpm -D
nginx -g 'daemon off;'
