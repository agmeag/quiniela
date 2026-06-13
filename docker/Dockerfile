#----------------------------------------------------------------
# Quiniela 2026 - Production Dockerfile
# Stage 1: Node — build frontend assets
# Stage 2: PHP 8.4 on Alpine 3.21 — runtime
#----------------------------------------------------------------

ARG ALPINE_VERSION=3.21

FROM node:22-alpine AS frontend
WORKDIR /app
COPY package.json package-lock.json vite.config.* tsconfig.json svelte.config.js ./
RUN npm ci
COPY resources ./resources
COPY public ./public
RUN npm run build

#----------------------------------------------------------------

FROM alpine:${ALPINE_VERSION}

LABEL maintainer="quiniela2026"
LABEL version="1.0.0"
LABEL description="Quiniela 2026 - Laravel 12 + Svelte 5"

WORKDIR /var/www/html

# Install system dependencies and PHP 8.4 in single layer
RUN apk add --no-cache \
    # Core system
    tini \
    bash \
    curl \
    git \
    nginx \
    supervisor \
    # PHP 8.4 (available in Alpine 3.21)
    php84 \
    php84-fpm \
    php84-opcache \
    php84-bcmath \
    php84-ctype \
    php84-curl \
    php84-dom \
    php84-fileinfo \
    php84-gd \
    php84-intl \
    php84-json \
    php84-mbstring \
    php84-openssl \
    php84-pdo \
    php84-pdo_pgsql \
    php84-phar \
    php84-posix \
    php84-session \
    php84-simplexml \
    php84-tokenizer \
    php84-xml \
    php84-xmlreader \
    php84-xmlwriter \
    php84-zip \
    php84-zlib \
    php84-exif \
    php84-pcntl \
    php84-iconv \
    php84-redis \
    # Image processing
    php84-pecl-imagick \
    imagemagick \
    # Networking tools for health checks
    netcat-openbsd \
    && ln -sf /usr/bin/php84 /usr/bin/php \
    && ln -sf /usr/sbin/php-fpm84 /usr/sbin/php-fpm

# Install Composer
RUN curl -sS https://getcomposer.org/installer | php -- \
    --install-dir=/usr/local/bin \
    --filename=composer \
    --version=2.7.7 && \
    chmod +x /usr/local/bin/composer && \
    composer --version

# Create www-data user with UID 1000 for host compatibility
RUN sed -i 's/^www-data:x:82:/www-data:x:1000:/' /etc/group && \
    adduser -u 1000 -S www-data -G www-data -s /bin/bash -h /var/www/html

# Copy configuration files
COPY ./docker/nginx.conf /etc/nginx/nginx.conf
COPY ./docker/php-fpm.conf /etc/php84/php-fpm.d/www.conf
COPY ./docker/php.ini /etc/php84/conf.d/99-custom.ini
COPY ./docker/supervisord.conf /etc/supervisor/conf.d/supervisord.conf
COPY ./docker/worker-laravel.conf /etc/supervisor/conf.d/worker-laravel.conf
COPY ./docker/worker-pulse.conf /etc/supervisor/conf.d/worker-pulse.conf
COPY ./docker/post-startup.sh /var/www/html/docker/post-startup.sh

# Copy application code with proper ownership
COPY --chown=www-data:www-data . .

# Copy frontend build from Node stage
COPY --from=frontend --chown=www-data:www-data /app/public/build ./public/build

# Set up directories and permissions
RUN mkdir -p \
    storage/logs \
    storage/framework/cache \
    storage/framework/sessions \
    storage/framework/views \
    bootstrap/cache \
    /var/lib/nginx/tmp \
    /var/log/supervisor \
    && chown -R www-data:www-data \
    storage \
    bootstrap/cache \
    /var/lib/nginx \
    /var/log/nginx \
    /var/log/supervisor \
    /run/nginx \
    && chmod -R 755 storage bootstrap/cache \
    && chmod +x /var/www/html/docker/post-startup.sh \
    && chmod -R 644 storage/logs/* || true

# Install PHP dependencies
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    --no-scripts \
    --prefer-dist

RUN chown -R www-data:www-data /var/www/html/vendor /var/www/html/composer.lock

# Pre-warm caches
RUN cp .env.example .env \
    && php artisan config:cache \
    && php artisan route:cache \
    && php artisan view:cache \
    && rm .env \
    && rm -rf bootstrap/cache/.gitignore \
        storage/framework/cache/.gitignore \
        storage/framework/sessions/.gitignore \
        storage/framework/views/.gitignore 2>/dev/null || true

# Copy entrypoint
COPY --chown=www-data:www-data ./docker/entrypoint.sh /entrypoint.sh
USER root
RUN chmod +x /entrypoint.sh

HEALTHCHECK --interval=30s --timeout=3s --start-period=15s --retries=3 \
    CMD nc -z localhost 80 && nc -z localhost 9000 || exit 1

EXPOSE 80 9000

ENTRYPOINT ["/sbin/tini", "--"]
CMD ["/entrypoint.sh"]
