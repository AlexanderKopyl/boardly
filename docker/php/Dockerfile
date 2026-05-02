FROM php:8.3-cli-alpine

RUN apk add --no-cache \
        bash \
        git \
        icu-dev \
        libzip-dev \
        linux-headers \
        oniguruma-dev \
        postgresql-dev \
        rabbitmq-c-dev \
        unzip \
        zip \
    && docker-php-ext-install \
        intl \
        opcache \
        pdo_pgsql \
        sockets \
        zip \
    && pecl install amqp \
    && docker-php-ext-enable amqp

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app
