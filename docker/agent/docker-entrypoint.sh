#!/bin/sh
set -e

# Настройка Git пользователя
if [ -n "$GIT_USER_NAME" ] && [ -n "$GIT_USER_EMAIL" ]; then
    echo "[entrypoint] Настраиваю Git пользователя..."
    git config --global user.name "$GIT_USER_NAME"
    git config --global user.email "$GIT_USER_EMAIL"
    echo "[entrypoint] Git пользователь настроен: $GIT_USER_NAME <$GIT_USER_EMAIL>"
else
    echo "[entrypoint] GIT_USER_NAME и GIT_USER_EMAIL не установлены - Git операции могут работать некорректно"
fi

# Настройка SSH ключа
if [ -n "$SSH_PRIVATE_KEY" ]; then
    echo "[entrypoint] Настраиваю SSH ключ..."

    mkdir -p ~/.ssh
    chmod 700 ~/.ssh

    # Записываем ключ из ENV
    echo "$SSH_PRIVATE_KEY" | tr -d '\r' > ~/.ssh/id_rsa
    chmod 600 ~/.ssh/id_rsa

    # Добавляем github/gitlab в known_hosts
    ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts 2>/dev/null
    ssh-keyscan -t rsa gitlab.com >> ~/.ssh/known_hosts 2>/dev/null
    chmod 644 ~/.ssh/known_hosts
    
    echo "[entrypoint] SSH ключ успешно настроен"
else
    echo "[entrypoint] SSH_PRIVATE_KEY не установлен - работа с приватными Git репозиториями будет недоступна"
fi

exec "$@"
