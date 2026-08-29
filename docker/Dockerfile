# Backend: Laravel (php-fpm + nginx) для «Календаря звонков»
FROM php:8.3-fpm

# Базовые значения окружения (переопределяются docker-compose)
ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:uERnaB7lePES+HDTG8Iac2F7T3wyN67q3fsdkNk4n4k= \
    DB_CONNECTION=mysql \
    DB_HOST=mysql \
    DB_PORT=3306 \
    DB_DATABASE=call_calendar \
    DB_USERNAME=root \
    DB_PASSWORD=secret

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx unzip libzip-dev \
    && docker-php-ext-install pdo_mysql zip \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/*

# Composer (официальный образ)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY backend/ .

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && chown -R www-data:www-data storage bootstrap/cache \
    && chmod -R ug+rw storage bootstrap/cache

COPY docker/backend/nginx.conf /etc/nginx/sites-available/default
COPY docker/backend/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
