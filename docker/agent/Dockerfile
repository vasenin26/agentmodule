FROM php:8.3-cli AS base

# Install system dependencies
RUN apt-get update && apt-get install -y \
    unzip \
    git \
    curl \
    libzip-dev \
    openssh-client \
    && docker-php-ext-install zip sockets

COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

RUN groupadd -g 1000 local && useradd -m -u 1000 -g 1000 local

# Копируем стартовый скрипт
COPY ./docker/agent/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

WORKDIR /app

ENTRYPOINT ["docker-entrypoint.sh"]

FROM base AS develop

RUN apt-get update && apt-get install -y inotify-tools make

FROM base AS production

# Копируем код приложения
COPY app/ /app/

# Устанавливаем зависимости
RUN composer install --no-dev --optimize-autoloader --no-interaction

# Устанавливаем рабочую директорию
WORKDIR /app

# Создаем пользователя для production
USER local