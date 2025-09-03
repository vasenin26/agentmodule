FROM php:8.3-cli AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    && docker-php-ext-install zip sockets

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

RUN groupadd -g 1000 local && useradd -m -u 1000 -g 1000 local


WORKDIR /app

FROM base AS develop

RUN apt-get update && apt-get install -y inotify-tools make
