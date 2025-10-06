# ══════════════════════════════════════════════════════════════
# Base Stage - общая база для всех вариантов
# ══════════════════════════════════════════════════════════════
FROM php:8.3-cli AS base

# Метаданные образа
LABEL maintainer="vasenin26"
LABEL org.opencontainers.image.title="AgentModule"
LABEL org.opencontainers.image.description="AI-powered documentation agent"
LABEL org.opencontainers.image.source="https://github.com/vasenin26/agentmodule"

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    # PHP расширения
    unzip \
    libzip-dev \
    # Git для работы с репозиториями
    git \
    # SSH клиент для Git операций
    openssh-client \
    # Утилиты
    curl \
    && docker-php-ext-install zip sockets \
    && rm -rf /var/lib/apt/lists/*

# Установка Composer
COPY --from=composer:2.6 /usr/bin/composer /usr/bin/composer

# Создание пользователя local (uid:gid = 1000:1000)
RUN groupadd -g 1000 local && useradd -m -u 1000 -g 1000 local

# Копирование entrypoint скрипта
COPY ./docker/agent/docker-entrypoint.sh /usr/local/bin/docker-entrypoint.sh
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

# Рабочая директория
WORKDIR /app

# Entrypoint для инициализации (Git user, SSH key)
ENTRYPOINT ["/usr/local/bin/docker-entrypoint.sh"]

# ══════════════════════════════════════════════════════════════
# Development Stage - для локальной разработки
# ══════════════════════════════════════════════════════════════
FROM base AS develop

LABEL variant="develop"
LABEL org.opencontainers.image.title="AgentModule - Development"

# Дополнительные инструменты для разработки
RUN apt-get update && apt-get install -y \
    inotify-tools \
    make \
    vim \
    && rm -rf /var/lib/apt/lists/*

# Не копируем код - будет через volume
# Не устанавливаем зависимости - будут через volume

# По умолчанию запускаем bash (для отладки)
CMD ["/bin/bash"]

# ══════════════════════════════════════════════════════════════
# Standalone Stage - автономный режим (текущий)
# Сам получает задачи в цикле, работает до исчерпания попыток
# ══════════════════════════════════════════════════════════════
FROM base AS standalone

LABEL variant="standalone"
LABEL org.opencontainers.image.title="AgentModule - Standalone"
LABEL org.opencontainers.image.description="Agent that polls tasks independently"

# Копирование кода приложения
COPY app/composer.json app/composer.lock ./
COPY app/ ./

# Установка зависимостей
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    && composer clear-cache

# Создание директорий для данных
RUN mkdir -p /home/local/repos/default && chown -R local:local /home/local

# Переключение на пользователя local для безопасности
USER local

# Точка входа - main.php (текущий Runner с циклом)
CMD ["php", "main.php"]

# ══════════════════════════════════════════════════════════════
# Orchestrated Stage - режим оркестратора (новый)
# Обрабатывает ОДНУ задачу от оркестратора и завершается
# ══════════════════════════════════════════════════════════════
FROM base AS orchestrated

LABEL variant="orchestrated"
LABEL org.opencontainers.image.title="AgentModule - Orchestrated"
LABEL org.opencontainers.image.description="Agent managed by AgentManager orchestrator"

# Копирование кода приложения
COPY app/composer.json app/composer.lock ./
COPY app/ ./

# Установка зависимостей
RUN composer install \
    --no-dev \
    --optimize-autoloader \
    --no-interaction \
    --no-progress \
    && composer clear-cache

# Создание директорий для данных
RUN mkdir -p /home/local/repos/orchestrated && chown -R local:local /home/local

# Переключение на пользователя local для безопасности
USER local

# Точка входа - orchestrated.php (новый OrchestratedRunner, одна задача)
CMD ["php", "orchestrated.php"]

# ══════════════════════════════════════════════════════════════
# Production Stage - алиас для orchestrated (для совместимости)
# ══════════════════════════════════════════════════════════════
FROM orchestrated AS production

LABEL variant="production"
LABEL org.opencontainers.image.title="AgentModule - Production"
