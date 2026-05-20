FROM php:8.3-apache AS production

ENV APP_ENV=prod \
    APP_DEBUG=0 \
    COMPOSER_ALLOW_SUPERUSER=1

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        acl \
        git \
        libicu-dev \
        libpng-dev \
        libzip-dev \
        unzip \
        zip \
    && docker-php-ext-install intl opcache pdo_mysql zip gd \
    && a2enmod rewrite headers \
    && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html
COPY composer.json composer.lock symfony.lock ./
RUN composer install --no-dev --no-interaction --prefer-dist --optimize-autoloader --no-scripts

COPY . .
COPY docker/apache.conf /etc/apache2/sites-available/000-default.conf
COPY docker/entrypoint.sh /usr/local/bin/hometab-entrypoint

RUN mkdir -p var/cache var/log config/jwt \
    && chown -R www-data:www-data var config/jwt \
    && chmod +x /usr/local/bin/hometab-entrypoint

EXPOSE 80
ENTRYPOINT ["hometab-entrypoint"]
CMD ["apache2-foreground"]
