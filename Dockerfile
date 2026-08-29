# Корневой Dockerfile: сборка и запуск «Календаря звонков» (SPA + API) на порту PORT.
# Используется hexlet-проверкой (docker build -f Dockerfile .) и деплоем.

# ---- Стадия 1: сборка frontend (Vue SPA) ----
FROM node:22-alpine AS frontend
WORKDIR /app

COPY frontend/package.json frontend/package-lock.json ./
RUN npm ci

COPY frontend/ .
RUN npm run build

# ---- Стадия 2: backend (Laravel + nginx) ----
FROM php:8.3-fpm AS backend

ENV APP_ENV=production \
    APP_DEBUG=false \
    APP_KEY=base64:uERnaB7lePES+HDTG8Iac2F7T3wyN67q3fsdkNk4n4k= \
    DB_CONNECTION=sqlite \
    CACHE_STORE=database \
    QUEUE_CONNECTION=database \
    SESSION_DRIVER=database \
    PORT=8000

RUN apt-get update \
    && apt-get install -y --no-install-recommends nginx unzip libzip-dev libsqlite3-dev \
    && docker-php-ext-install pdo_mysql pdo_sqlite zip \
    && docker-php-ext-enable opcache \
    && rm -rf /var/lib/apt/lists/* \
    && rm -f /etc/nginx/sites-enabled/default

# Composer (официальный образ)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www

COPY backend/ .

RUN composer install --no-dev --prefer-dist --no-interaction --optimize-autoloader \
    && mkdir -p database \
    && touch database/database.sqlite \
    && chown -R www-data:www-data storage bootstrap/cache database \
    && chmod -R ug+rw storage bootstrap/cache database

# Собранный SPA кладём в public/ (nginx отдаёт index.html + /assets, /api → Laravel)
COPY --from=frontend /app/dist ./public

COPY docker/nginx.conf.template /etc/nginx/conf.d/default.conf.template
COPY docker/entrypoint.app.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 8000

ENTRYPOINT ["/usr/local/bin/entrypoint.sh"]
