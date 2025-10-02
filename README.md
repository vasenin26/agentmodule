# agentmodule

## SSH ключи для Git репозиториев

Для работы с приватными Git репозиториями в Docker контейнере настроена автоматическая настройка SSH ключей.

### Быстрая настройка

1. Скопируйте файл с примером конфигурации:
   ```bash
   cp env.example .env
   ```

2. Добавьте настройки Git пользователя и SSH ключ в `.env` файл:
   ```env
   GIT_USER_NAME="Your Name"
   GIT_USER_EMAIL="your.email@example.com"
   SSH_PRIVATE_KEY="-----BEGIN OPENSSH PRIVATE KEY-----
   MIICWwIBAAKBgQDFwR8qL3N5K+JdB...
   -----END OPENSSH PRIVATE KEY-----"
   ```

3. Запустите контейнер:
   ```bash
   docker-compose up --build
   ```

### Запуск тестов

```
docker compose run --rm agentmodule php vendor/bin/phpunit
```

### Создание релизов

Для создания нового релиза используйте команды из Makefile:

```bash
# Создать patch релиз (v1.0.0 -> v1.0.1)
make bump-patch

# Создать minor релиз (v1.0.0 -> v1.1.0)
make bump-minor
```

После создания тега GitHub Actions автоматически:
- Соберет Docker образ
- Опубликует его в GitHub Container Registry (ghcr.io)
- Создаст тег `latest` для последней версии

### Использование production образа

```bash
# Запуск production версии
docker-compose -f docker-compose.prod.yaml up

# Или с конкретной версией
docker run -it ghcr.io/vasenin26/agentmodule:v1.0.0
```

### Подробная документация

Подробные инструкции по настройке SSH ключей доступны в [docs/ssh-setup.md](docs/ssh-setup.md).

### Безопасность

⚠️ **Важно:**
- Никогда не коммитьте `.env` файл с ключами в репозиторий
- В продакшене используйте Docker secrets или менеджер секретов
- Убедитесь, что `.env` добавлен в `.gitignore`