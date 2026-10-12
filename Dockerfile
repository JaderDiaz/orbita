# syntax=docker/dockerfile:1

FROM dunglas/frankenphp:1-php8.5 AS base

LABEL org.opencontainers.image.title="Orbita"
LABEL org.opencontainers.image.description="Sistema de gestion para agencia de desarrollo"

# install-php-extensions ships with the FrankenPHP image.
RUN install-php-extensions \
        pdo_pgsql \
        pgsql \
        intl \
        bcmath \
        gd \
        zip \
        redis \
        opcache \
        pcntl

RUN apt-get update \
    && apt-get install -y --no-install-recommends git unzip curl ca-certificates gnupg \
    && rm -rf /var/lib/apt/lists/*

# Node is required in this image because the Wayfinder Vite plugin shells out to
# `php artisan wayfinder:generate`, so the Vite dev server cannot run in a PHP-less container.
ARG NODE_MAJOR=24
RUN curl -fsSL "https://deb.nodesource.com/setup_${NODE_MAJOR}.x" | bash - \
    && apt-get install -y --no-install-recommends nodejs \
    && rm -rf /var/lib/apt/lists/* \
    && corepack enable

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

COPY docker/app/entrypoint.sh /usr/local/bin/orbita-entrypoint
RUN chmod +x /usr/local/bin/orbita-entrypoint

# FrankenPHP serves /app/public and listens on the port given by SERVER_NAME.
ENV SERVER_NAME=":80"

ENTRYPOINT ["/usr/local/bin/orbita-entrypoint"]
CMD ["frankenphp", "run", "--config", "/etc/frankenphp/Caddyfile"]
