# syntax=docker/dockerfile:1

ARG PHP_VERSION=8.3
FROM php:${PHP_VERSION}-cli AS base

ENV COMPOSER_ALLOW_SUPERUSER=1 \
    COMPOSER_NO_INTERACTION=1 \
    PATH="/root/.composer/vendor/bin:${PATH}"

# Install system deps and PHP extensions needed for Composer and this library
RUN apt-get update \
    && apt-get install -y --no-install-recommends \
       git \
       unzip \
       libzip-dev \
       zlib1g-dev \
    && docker-php-ext-install zip \
    # Install Xdebug for code coverage
    && pecl install xdebug \
    && docker-php-ext-enable xdebug \
    && rm -rf /var/lib/apt/lists/*

# Ensure Xdebug coverage is enabled by default for test runs
ENV XDEBUG_MODE=coverage

# Install Composer (from official image)
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /app

# Leverage Composer layer caching
COPY composer.json composer.lock ./
RUN composer install --prefer-dist --no-progress --no-suggest

# Copy the rest of the source
COPY . .

# Ensure dev dependencies are present (tests, linters, etc.)
RUN composer install --prefer-dist --no-progress

# Default command runs the test suite
CMD ["vendor/bin/phpunit", "-c", "phpunit.xml"]


