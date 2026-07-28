# syntax=docker/dockerfile:1

# Laradox PHP image (FrankenPHP + Laravel Octane).
#
# Build args:
#   FRANKENPHP_VERSION / PHP_VERSION - base image coordinates
#   ENVIRONMENT                      - development | production (selects the final stage)
#   USER_ID / GROUP_ID               - host uid/gid, so bind-mounted files stay writable
#
# The runtime port is read from the LARADOX_FRANKENPHP_PORT *environment* variable
# (not a build arg), so a single image can be started on any port.

ARG FRANKENPHP_VERSION=1.12
ARG PHP_VERSION=8.4
ARG ENVIRONMENT=development


# STAGE 1: Builder — compiles the extensions, then gets thrown away.
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-alpine AS builder

ARG TARGETARCH
ARG SUPERCRONIC_VERSION=0.2.48

# Build-time headers only. mbstring, dom/xml, sqlite, curl and opcache already
# ship with the base image, so their dev packages are deliberately not here.
RUN apk add --no-cache \
    $PHPIZE_DEPS \
    curl \
    freetype-dev \
    icu-dev \
    jpeg-dev \
    libpng-dev \
    libuv-dev \
    libwebp-dev \
    libzip-dev \
    linux-headers \
    postgresql-dev

# Bundled + PECL extensions in one layer to keep the image thin.
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        gd \
        intl \
        pcntl \
        pdo_mysql \
        pdo_pgsql \
        zip \
    && pecl channel-update pecl.php.net \
    && pecl install redis excimer channel://pecl.php.net/uv-0.3.0 \
    && docker-php-ext-enable redis excimer uv \
    && rm -rf /tmp/pear /usr/local/lib/php/extensions/*/*.a

# Composer + Supercronic (arch-aware, so arm64 hosts build too).
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN curl -fsSLo /usr/local/bin/supercronic \
        "https://github.com/aptible/supercronic/releases/download/v${SUPERCRONIC_VERSION}/supercronic-linux-${TARGETARCH}" \
    && chmod +x /usr/local/bin/supercronic


# STAGE 2: Base — shared runtime for both environments.
FROM dunglas/frankenphp:${FRANKENPHP_VERSION}-php${PHP_VERSION}-alpine AS base

ARG USER_ID=1000
ARG GROUP_ID=1000

# Shared libraries the extensions above link against, plus supervisor for the
# production queue container.
RUN apk add --no-cache \
    freetype \
    icu-libs \
    jpeg \
    libpng \
    libpq \
    libuv \
    libwebp \
    libzip \
    supervisor

COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer
COPY --from=builder /usr/local/bin/supercronic /usr/local/bin/supercronic
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

COPY supervisord.conf /etc/supervisord.conf
COPY laravel-worker.conf /etc/supervisord.d/laravel-worker.conf

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_HOME=/config/composer \
    COMPOSER_CACHE_DIR=/home/composer/.cache/composer \
    LARADOX_FRANKENPHP_PORT=8080

# /data/caddy and /config/caddy are FrankenPHP's state dirs; the composer paths
# match the cache/auth.json volumes in docker-compose.development.yml.
RUN addgroup -g ${GROUP_ID} -S appgroup \
    && adduser -u ${USER_ID} -S appuser -G appgroup \
    && mkdir -p /data/caddy /config/caddy "${COMPOSER_HOME}" "${COMPOSER_CACHE_DIR}" \
    && chown -R appuser:appgroup /data /config /home/composer

WORKDIR /srv
EXPOSE ${LARADOX_FRANKENPHP_PORT}


# STAGE 3: Development
FROM base AS development

ARG USER_ID=1000
ARG GROUP_ID=1000

# JIT stays off and timestamps are validated so --watch reloads pick up edits.
RUN cp "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'memory_limit=512M' \
        'upload_max_filesize=25M' \
        'post_max_size=25M' \
        'opcache.enable=1' \
        'opcache.enable_cli=1' \
        'opcache.validate_timestamps=1' \
        'opcache.revalidate_freq=0' \
        > "$PHP_INI_DIR/conf.d/zz-laradox.ini" \
    && mkdir -p /opt/phpstorm-coverage \
    && chown -R ${USER_ID}:${GROUP_ID} /opt/phpstorm-coverage

USER appuser

# `exec` so PHP becomes PID 1 and receives SIGTERM directly, which lets Octane
# drain in-flight requests instead of being killed with the shell.
CMD ["sh", "-c", "exec php artisan octane:frankenphp --watch --host=0.0.0.0 --port=\"${LARADOX_FRANKENPHP_PORT:-8080}\""]


# STAGE 4: Production
FROM base AS production

# Timestamp validation off + tracing JIT: the usual Octane production profile.
# Requires a deploy-time image rebuild (or `octane:reload`) to pick up new code.
# enable_cli is on for the long-running CLI workers (queue:work, supercronic).
RUN cp "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini" \
    && printf '%s\n' \
        'memory_limit=512M' \
        'upload_max_filesize=25M' \
        'post_max_size=25M' \
        'opcache.enable=1' \
        'opcache.enable_cli=1' \
        'opcache.memory_consumption=256' \
        'opcache.interned_strings_buffer=32' \
        'opcache.max_accelerated_files=20000' \
        'opcache.validate_timestamps=0' \
        'opcache.jit=tracing' \
        'opcache.jit_buffer_size=128M' \
        > "$PHP_INI_DIR/conf.d/zz-laradox.ini"

USER appuser

# See the development stage for why this is wrapped in `sh -c exec`.
CMD ["sh", "-c", "exec php artisan octane:frankenphp --host=0.0.0.0 --port=\"${LARADOX_FRANKENPHP_PORT:-8080}\""]


# FINAL: alias for the stage named by ENVIRONMENT, so `docker compose build`
# without an explicit --target still produces the right image.
FROM ${ENVIRONMENT}
