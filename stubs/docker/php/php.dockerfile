ARG ENVIRONMENT=development

# STAGE 1: Builder
FROM dunglas/frankenphp:1.7-php8.4-alpine AS builder

RUN apk add --no-cache \
    $PHPIZE_DEPS \
    curl-dev \
    git \
    icu-dev \
    libxml2-dev \
    libzip-dev \
    libpng-dev \
    oniguruma-dev \
    linux-headers \
    postgresql-dev \
    libuv-dev \
    supervisor \
    && mkdir -p /etc/supervisord.d/

# Redis via PECL
RUN pecl install redis \
    && docker-php-ext-enable redis

# Excimer via PECL
RUN pecl channel-update pecl.php.net \
    && pecl install excimer \
    && docker-php-ext-enable excimer

# Install ext-uv (event loop)
RUN pecl install channel://pecl.php.net/uv-0.3.0 \
    && docker-php-ext-enable uv

# PHP extensions default
RUN docker-php-ext-install -j$(nproc) \
    bcmath \
    gd \
    intl \
    pcntl \
    pdo_pgsql \
    mbstring \
    zip

# Composer & Supercronic
COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN curl -fsSLo /usr/local/bin/supercronic https://github.com/aptible/supercronic/releases/download/v0.2.29/supercronic-linux-amd64 \
    && chmod +x /usr/local/bin/supercronic


# STAGE 2: Base
FROM dunglas/frankenphp:1.7-php8.4-alpine AS base

ARG USER_ID=1000
ARG GROUP_ID=1000

COPY --from=builder /usr/local/bin/composer /usr/local/bin/composer
COPY --from=builder /usr/local/bin/supercronic /usr/local/bin/supercronic

# copy extensions & conf
COPY --from=builder /usr/local/lib/php/extensions/ /usr/local/lib/php/extensions/
COPY --from=builder /usr/local/etc/php/conf.d/ /usr/local/etc/php/conf.d/

# RUNTIME libs
RUN apk add --no-cache \
    icu-libs \
    libxml2 \
    libzip \
    libpng \
    oniguruma \
    libpq \
    libuv \
    supervisor

COPY supervisord.conf /etc/supervisord.conf
COPY laravel-worker.conf /etc/supervisord.d/laravel-worker.conf

RUN addgroup -g ${GROUP_ID} -S appgroup && \
    adduser -u ${USER_ID} -S appuser -G appgroup

RUN mkdir -p /data/caddy && chown -R appuser:appgroup /data


# STAGE 3: Development
FROM base AS development
ARG USER_ID
ARG GROUP_ID
RUN cp $PHP_INI_DIR/php.ini-development $PHP_INI_DIR/php.ini
RUN mkdir -p /opt/phpstorm-coverage && \
    chown -R ${USER_ID}:${GROUP_ID} /opt/phpstorm-coverage


# STAGE 4: Production
FROM base AS production
RUN cp $PHP_INI_DIR/php.ini-production $PHP_INI_DIR/php.ini


# FINAL
FROM ${ENVIRONMENT}
ARG ENVIRONMENT
CMD if [ "$ENVIRONMENT" = "production" ]; then \
        php artisan octane:frankenphp; \
    else \
        php artisan octane:frankenphp --watch; \
    fi
WORKDIR /srv
USER appuser
