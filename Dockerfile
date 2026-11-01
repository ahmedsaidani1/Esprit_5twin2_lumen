# syntax=docker/dockerfile:1

# ---------- Étape 1 : dépendances PHP (sans les outils de développement) ----------
FROM composer:2 AS vendor
WORKDIR /app
COPY composer.json composer.lock ./
RUN composer install --no-dev --no-interaction --no-scripts --no-autoloader --prefer-dist --ignore-platform-reqs
COPY . .
RUN composer dump-autoload --no-dev --optimize --classmap-authoritative

# ---------- Étape 2 : image d'exécution (Apache + PHP 8.3) ----------
FROM php:8.3-apache

# Extensions PHP nécessaires : MySQL, images (dompdf), zip, intl, bcmath, opcache
RUN apt-get update && apt-get install -y --no-install-recommends \
        curl libzip-dev libpng-dev libjpeg62-turbo-dev libfreetype6-dev libicu-dev default-mysql-client \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" pdo_mysql gd zip intl bcmath opcache \
    && apt-get clean && rm -rf /var/lib/apt/lists/*

# Apache sert le dossier public/ et active la réécriture d'URL de Laravel
ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf /etc/apache2/apache2.conf \
    && a2enmod rewrite headers
COPY docker/apache/security.conf /etc/apache2/conf-enabled/zz-security.conf
COPY docker/php.ini /usr/local/etc/php/conf.d/zz-solarshare.ini

WORKDIR /var/www/html
COPY --from=vendor /app /var/www/html
RUN chown -R www-data:www-data storage bootstrap/cache

COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Le même image sert de serveur web, de "worker" de file d'attente ou de planificateur (CONTAINER_ROLE)
ENV CONTAINER_ROLE=app
EXPOSE 80
HEALTHCHECK --interval=30s --timeout=5s --start-period=40s --retries=3 \
    CMD [ "$CONTAINER_ROLE" != "app" ] || curl -fsS http://localhost/up || exit 1

ENTRYPOINT ["entrypoint"]
