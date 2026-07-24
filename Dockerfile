FROM php:8.4-fpm-alpine

WORKDIR /var/www/html

RUN apk add --no-cache \
        bash \
        curl \
        git \
        icu-dev \
        libpng-dev \
        libzip-dev \
        oniguruma-dev \
        unzip \
        zip \
        linux-headers \
        $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install \
        bcmath \
        exif \
        intl \
        mbstring \
        pcntl \
        pdo_mysql \
        zip \
    && pecl install redis \
    && docker-php-ext-enable redis \
    && rm -rf /tmp/pear

COPY --from=composer:2.8 /usr/bin/composer /usr/bin/composer

COPY docker/php/local.ini /usr/local/etc/php/conf.d/local.ini

EXPOSE 9000

CMD ["php-fpm"]
