# План интеграции агента с AgentManager Orchestrator

## 📋 Обзор

Этот документ описывает необходимые изменения в агенте (PHP) для интеграции с оркестратором AgentManager (Go), который использует pull-based модель управления задачами.

**Дата создания:** 2025-10-06  
**Версия:** 2.0 (обновлен: учтены предложения по entrypoint и раздельным точкам входа)  
**Проект агента:** agentmodule (PHP)  
**Проект оркестратора:** agentmanager (Go)

### 🎉 Ключевые архитектурные решения

1. **Docker entrypoint для SSH** - Инициализация SSH ключей в bash-скрипте (не в PHP)
2. **Две явные точки входа** - `main.php` (standalone) и `orchestrated.php` (orchestrated)
3. **Без переключателя RUN_MODE** - Режим определяется явно через CMD в Dockerfile
4. **Чистая архитектура** - `main.php` остается без изменений

---

## 🎯 Цель интеграции

Адаптировать PHP-агент для работы под управлением AgentManager оркестратора при сохранении возможности работы в standalone режиме.

### Два Docker образа

**1️⃣ Standalone образ** (`agentmodule:standalone`)
- Текущее поведение агента
- Сам получает задачи в бесконечном цикле
- Генерирует собственные SSH ключи
- Используется для локальной разработки и автономной работы

**2️⃣ Orchestrated образ** (`agentmodule:orchestrated` / `agentmodule:latest`)
- Новое поведение для оркестратора
- Получает ОДНУ задачу через переменные окружения
- Использует SSH ключи проекта от оркестратора
- Выполняет задачу и завершается (exit 0/1)
- Используется AgentManager оркестратором

### Требования к оркестратору

AgentManager оркестратор:
- Использует pull-based модель получения задач из внешнего API
- Генерирует SSH ключи на уровне проектов (не агентов!)
- Передает задачи через переменные окружения Docker контейнера
- Управляет контекстами через Docker volumes

---

## 🔑 Ключевые концепции оркестратора

### Pull-based модель
```
Оркестратор                    External API                    Агент
     │                              │                            │
     │─── GET /tasks/next ────────▶│                            │
     │◀── task-123 ────────────────│                            │
     │                              │                            │
     │─── POST /tasks/123/reserve ─▶│                            │
     │    {agent_uuid, reserve_seconds}                          │
     │◀── 200 OK ──────────────────│                            │
     │                              │                            │
     │─── docker run ──────────────────────────────────────────▶│
     │    Env: TASK_ID=task-123                                 │
     │         AGENT_UUID=uuid-456                              │
     │         SSH_PRIVATE_KEY=<project-key>                    │
     │                              │                            │
     │                              │◀─── POST /api/agent/task ──│
     │                              │     {agent_uuid}           │
     │                              │                            │
     │                              │──── task details ─────────▶│
     │                              │                            │
     │                              │                     (agent works)
     │                              │                            │
     │                              │◀─── PUT /api/agent/task ───│
     │                              │     {completed: true}      │
```

### SSH ключи на уровне проектов
```
❗ КРИТИЧЕСКИ ВАЖНО: Ключи генерируются ОРКЕСТРАТОРОМ, не агентами!

Оркестратор:
  1. Генерирует SSH ключи ОДИН РАЗ для проекта
  2. Публичный ключ → External API → GitHub/GitLab
  3. Приватный ключ → передает ВСЕМ агентам проекта через ENV

Все агенты одного проекта используют ОДИНАКОВЫЙ SSH ключ!
```

### Жизненный цикл задачи
1. Оркестратор получает задачу через External API
2. Оркестратор резервирует задачу с `agent_uuid`
3. Оркестратор запускает Docker контейнер с агентом
4. **Агент получает TASK_ID через переменную окружения**
5. Агент вызывает `POST /api/agent/task` для получения полных данных
6. Агент выполняет задачу
7. Агент вызывает `PUT /api/agent/task/{id}` для завершения
8. Контейнер завершается (exit 0)

---

## 🚨 Критические изменения в агенте

### ❌ Что НУЖНО УБРАТЬ из агента

1. **Генерация SSH ключей**
   ```php
   // ❌ УДАЛИТЬ: агент НЕ генерирует ключи!
   $sshStorage->generateAndStoreKeyPair($agentUuid);
   ```
   
2. **Бесконечный цикл получения задач**
   ```php
   // ❌ УДАЛИТЬ: агент НЕ получает задачи в цикле!
   while (true) {
       $task = $this->api->getTask($agentUuid);
       // ...
   }
   ```

3. **Повторные попытки получения задач**
   ```php
   // ❌ УДАЛИТЬ: агент НЕ делает повторные запросы!
   const GET_TASK_ATTEMPTS = 10;
   ```

### ✅ Что НУЖНО ДОБАВИТЬ в агент

1. **Чтение TASK_ID из переменной окружения**
   ```php
   // ✅ ДОБАВИТЬ: получаем ID задачи из ENV
   $taskId = getenv('TASK_ID');
   $agentUuid = getenv('AGENT_UUID');
   ```

2. **Получение задачи по ID**
   ```php
   // ✅ ДОБАВИТЬ: запрашиваем задачу по ID с agent_uuid
   POST /api/agent/task
   Body: {"agent_uuid": "uuid-456"}
   ```

3. **Использование SSH ключа проекта**
   ```php
   // ✅ ДОБАВИТЬ: используем ключ из ENV (ключ ПРОЕКТА!)
   $projectSshKey = getenv('SSH_PRIVATE_KEY');
   ```

---

## 📁 Изменяемые файлы

### 1. `app/main.php` - точка входа
**Приоритет:** 🔴 КРИТИЧЕСКИЙ  
**Изменения:** Полная переработка

**Было:**
```php
(new Runner($api, StateStore::run(), $processorFactory))->run();
```

**Станет:**
```php
(new OrchestratedRunner($api, StateStore::run(), $processorFactory))->run();
```

### 2. `app/src/Runner.php` - основная логика
**Приоритет:** 🔴 КРИТИЧЕСКИЙ  
**Изменения:** Новый класс `OrchestratedRunner`

**Создать:** `app/src/OrchestratedRunner.php`

### 3. API клиент для получения задачи
**Приоритет:** 🔴 КРИТИЧЕСКИЙ  
**Изменения:** Новый метод в `TaskApi`

**Создать/обновить:** API метод для получения задачи по UUID

### 4. SSH ключи
**Приоритет:** 🔴 КРИТИЧЕСКИЙ  
**Изменения:** Использование ключа из ENV

---

## 🔨 Детальный план реализации

## Этап 1: Создание OrchestratedRunner (2-3 часа)

### 1.1 Создать новый класс

**Файл:** `app/src/OrchestratedRunner.php`

```php
<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Services\StateStoreInterface;
use Anymodule\Agentmodule\Utils\Log;

final readonly class OrchestratedRunner
{
    const STORE_AGENT_STATUS_KEY = 'status';

    public function __construct(
        private TaskApiInterface                       $api,
        private StateStoreInterface           $stateStore,
        private TaskProcessorFactoryInterface $processorFactory,
    )
    {
    }

    public function run(): void
    {
        // Получить переменные окружения от оркестратора
        $taskId = getenv('TASK_ID');
        $agentUuid = getenv('AGENT_UUID');
        $agentId = getenv('AGENT_ID');

        if (!$taskId || !$agentUuid || !$agentId) {
            Log::error('Missing required environment variables', [
                'TASK_ID' => $taskId ?: 'not set',
                'AGENT_UUID' => $agentUuid ?: 'not set',
                'AGENT_ID' => $agentId ?: 'not set',
            ]);
            exit(1);
        }

        $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'started');

        Log::info("Agent started by orchestrator", [
            'agent_id' => $agentId,
            'agent_uuid' => $agentUuid,
            'task_id' => $taskId,
        ]);

        try {
            // Получить полные данные задачи из External API
            Log::info("Fetching task details", ['task_id' => $taskId]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'fetching');
            $task = $this->api->getTaskByUuid($agentUuid);

            if (is_null($task)) {
                Log::error("Failed to fetch task", [
                    'task_id' => $taskId,
                    'agent_uuid' => $agentUuid,
                ]);
                $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'error');
                exit(1);
            }

            Log::info("Task fetched successfully", [
                'task_id' => $task->id,
                'handler' => $task->handler ?? 'unknown',
            ]);

            // Обработать задачу
            Log::info("Processing task", ['task_id' => $task->id]);
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'processing');

            $handler = new DocsModule($this->api, $agentId, $task);
            $this->processorFactory->createProcessorForTask($task)
                ->process($task, $handler);

            Log::info("Task completed successfully", ['task_id' => $task->id]);
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'completed');
            
            // Успешное завершение
            exit(0);

        } catch (\Throwable $e) {
            Log::exception($e, 'Task processing failed', [
                'agent_id' => $agentId,
                'agent_uuid' => $agentUuid,
                'task_id' => $taskId,
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'failed');
            
            // Завершение с ошибкой
            exit(1);
        }
    }
}
```

**Ключевые моменты:**
- ✅ Получает `TASK_ID`, `AGENT_UUID`, `AGENT_ID` из ENV
- ✅ Валидирует обязательные переменные
- ✅ Вызывает `getTaskByUuid()` для получения полных данных задачи
- ✅ Обрабатывает ОДНУ задачу и завершается
- ✅ Exit code: 0 = success, 1 = error
- ❌ НЕТ бесконечного цикла
- ❌ НЕТ повторных попыток

---

## Этап 2: Расширение API клиента (1-2 часа)

### 2.1 Добавить метод `getTaskByUuid()`

**Файл:** `app/src/Interface/Task/TaskApi.php`

```php
public function getTaskByUuid(string $agentUuid): ?TaskDTO;
```

### 2.2 Реализовать в `Service.php`

**Файл:** `app/src/Services/ApiService/Service.php`

```php
public function getTaskByUuid(string $agentUuid): ?TaskDTO
{
    try {
        Log::info("Requesting task by UUID", ['agent_uuid' => $agentUuid]);
        
        $response = $this->httpClient->post(
            $this->host . '/api/agent/task',
            [
                'json' => [
                    'agent_uuid' => $agentUuid,
                ],
                'headers' => [
                    'Authorization' => 'Bearer ' . $this->token,
                    'Accept' => 'application/json',
                ],
            ]
        );

        if ($response->getStatusCode() === 200) {
            $data = json_decode($response->getBody()->getContents(), true);
            
            Log::info("Task received", [
                'task_id' => $data['id'] ?? 'unknown',
                'status' => $data['status'] ?? 'unknown',
            ]);
            
            // Маппинг в TaskDTO согласно вашей структуре
            return $this->mapToTaskDTO($data);
        }
        
        if ($response->getStatusCode() === 404) {
            Log::warning("Task not found", ['agent_uuid' => $agentUuid]);
            return null;
        }
        
        Log::error("Unexpected response", [
            'status_code' => $response->getStatusCode(),
            'agent_uuid' => $agentUuid,
        ]);
        
        return null;
        
    } catch (\Throwable $e) {
        Log::exception($e, 'Failed to get task by UUID', [
            'agent_uuid' => $agentUuid,
        ]);
        
        return null;
    }
}

private function mapToTaskDTO(array $data): TaskDTO
{
    // Маппинг согласно структуре External API
    // См. документацию: agentmanager/docs/task-api-specification.md
    return new TaskDTO(
        id: $data['id'],
        handler: $data['handler'] ?? null,
        handlerOptions: $data['handler_options'] ?? [],
        projectId: $data['project_id'] ?? null,
        // ... другие поля согласно вашей структуре TaskDTO
    );
}
```

**Ключевые моменты:**
- ✅ Отправляет `agent_uuid` в теле запроса
- ✅ Использует `Authorization: Bearer {API_TOKEN}`
- ✅ Обрабатывает 200 (success), 404 (not found)
- ✅ Логирует все операции
- ✅ Возвращает `null` при ошибке

---

## Этап 3: SSH ключи проекта (30 минут)

### 3.1 Docker entrypoint для инициализации SSH

**Схема инициализации:**
```
┌─────────────────────────────────────────────────────────────┐
│  Контейнер запущен с ENV: SSH_PRIVATE_KEY=-----BEGIN...    │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  ENTRYPOINT: /docker-entrypoint.sh                          │
│    ├─ Читает SSH_PRIVATE_KEY из ENV                         │
│    ├─ Создает /root/.ssh/project_key (права 600)            │
│    ├─ Создает /root/.ssh/config                             │
│    └─ Exec "$@" (запускает CMD)                             │
└───────────────────────────┬─────────────────────────────────┘
                            │
                            ▼
┌─────────────────────────────────────────────────────────────┐
│  CMD: php main.php или php orchestrated.php                 │
│                                                             │
│  SSH ключ УЖЕ настроен!                                     │
│  /root/.ssh/project_key    - приватный ключ                │
│  /root/.ssh/config         - конфигурация SSH              │
│                                                             │
│  Git команды автоматически используют этот ключ:           │
│    git clone git@github.com:user/repo.git                  │
└─────────────────────────────────────────────────────────────┘
```

**Создать:** `docker/agent/docker-entrypoint.sh`

```bash
#!/bin/sh
set -e

# Цвета для логов
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
NC='\033[0m' # No Color

echo "${GREEN}[Entrypoint]${NC} Starting agent container initialization..."

# Инициализация SSH ключа проекта (если предоставлен оркестратором)
if [ -n "$SSH_PRIVATE_KEY" ]; then
    echo "${GREEN}[Entrypoint]${NC} Initializing project SSH key..."
    
    # Создать директорию .ssh если нет
    mkdir -p /root/.ssh
    chmod 700 /root/.ssh
    
    # Сохранить приватный ключ проекта
    echo "$SSH_PRIVATE_KEY" > /root/.ssh/project_key
    chmod 600 /root/.ssh/project_key
    
    # Создать SSH config для автоматического использования ключа
    cat > /root/.ssh/config <<EOF
# Project SSH key (provided by orchestrator)
Host github.com gitlab.com
    IdentityFile /root/.ssh/project_key
    StrictHostKeyChecking no
    UserKnownHostsFile /dev/null
    LogLevel ERROR
EOF
    chmod 600 /root/.ssh/config
    
    echo "${GREEN}[Entrypoint]${NC} Project SSH key initialized successfully"
else
    echo "${YELLOW}[Entrypoint]${NC} SSH_PRIVATE_KEY not provided (standalone mode or SSH not required)"
fi

# Вывести информацию о режиме работы
if [ -n "$TASK_ID" ] && [ -n "$AGENT_UUID" ]; then
    echo "${GREEN}[Entrypoint]${NC} Running in ORCHESTRATED mode"
    echo "${GREEN}[Entrypoint]${NC}   TASK_ID: $TASK_ID"
    echo "${GREEN}[Entrypoint]${NC}   AGENT_UUID: $AGENT_UUID"
else
    echo "${GREEN}[Entrypoint]${NC} Running in STANDALONE mode"
fi

echo "${GREEN}[Entrypoint]${NC} Initialization complete. Starting application..."
echo ""

# Запустить основное приложение (передать все аргументы)
exec "$@"
```

**Сделать исполняемым:**
```bash
chmod +x docker/agent/docker-entrypoint.sh
```

### 3.2 Использование SSH ключа в Git операциях

**Файл:** Где выполняются Git операции (например, `RepositoryService`)

SSH ключ уже настроен в entrypoint, просто используйте Git команды:

```php
// ✅ Просто используем git команды - ключ уже настроен!
exec('git clone git@github.com:user/repo.git');
exec('git push origin main');

// Или с явным указанием (опционально):
putenv("GIT_SSH_COMMAND=ssh -i /root/.ssh/project_key -o StrictHostKeyChecking=no");
exec('git clone git@github.com:user/repo.git');
```

### 3.3 Удаление генерации ключей агентом

**Найти и удалить весь код генерации SSH ключей:**

```php
// ❌ УДАЛИТЬ - агент НЕ генерирует ключи!
$sshStorage->generateAndStoreKeyPair($agentUuid);
$sshKey = $this->sshKeyGenerator->generate();
// и т.д.
```

**Ключевые моменты:**
- ✅ SSH ключ инициализируется в **entrypoint** (до запуска PHP)
- ✅ Работает для standalone и orchestrated режимов
- ✅ Сохраняется в `/root/.ssh/project_key` с правами 600
- ✅ Автоматически настраивается в SSH config
- ✅ Доступен для всех Git операций
- ✅ Нет PHP кода для инициализации SSH
- ✅ Безопасно и эффективно
- ❌ НЕ генерирует новые ключи в PHP
- ❌ НЕ создает временные файлы

---

## Этап 4: Две точки входа (30 минут)

### 4.1 Оставить `main.php` без изменений (standalone)

**Файл:** `app/main.php` - НЕ МЕНЯЕМ!

Это точка входа для **standalone** режима (текущая версия).

```php
<?php
// Текущий код остается без изменений
// Это точка входа для standalone образа

use Anymodule\Agentmodule\Factory\ActionsFactory;
// ... все как есть

(new Runner($api, StateStore::run(), $processorFactory))->run();
```

### 4.2 Создать `orchestrated.php` (новая точка входа)

**Файл:** `app/orchestrated.php` - НОВЫЙ!

Это точка входа для **orchestrated** режима.

```php
<?php

use Anymodule\Agentmodule\Factory\ActionsFactory;
use Anymodule\Agentmodule\Factory\ConversationFactory;
use Anymodule\Agentmodule\Factory\ChatAgentFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\OrchestratedRunner;
use Anymodule\Agentmodule\Services\ApiService\DocModuleApi;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\StateStore;
use Anymodule\Agentmodule\Services\TaskStorageProvider;
use Anymodule\Agentmodule\Utils\Log;

require __DIR__ . '/vendor/autoload.php';

Log::info("Agent starting in ORCHESTRATED mode (managed by AgentManager)");

// Инициализация сервисов
$api = new DocModuleApi(
    host: getenv('API_HOST'),
    token: getenv('API_TOKEN'),
);

$repoProvider = new RepositoryProvider(reposFolder: 'default', branch: 'main');

$toolFactory = new ToolServiceFactory(
    $repoProvider,
    new PageContextProviderFactory($api),
);

$llmFactory = new ChatAgentFactory($repoProvider);

$processorFactory = new TaskProcessorFactory(
    $toolFactory,
    $llmFactory,
    new ConversationFactory(),
    new TaskStorageProvider(),
    new ActionsFactory($toolFactory, $llmFactory),
);

// Запуск orchestrated runner
(new OrchestratedRunner($api, StateStore::run(), $processorFactory))->run();
```

**Ключевые моменты:**
- ✅ Отдельный файл - чистая архитектура
- ✅ Нет условной логики (if/else)
- ✅ Явно указывается в CMD образа
- ✅ Легко читать и поддерживать
- ✅ `main.php` остается нетронутым (обратная совместимость)

---

## Этап 5: Dockerfile - два образа (1-2 часа)

### 5.1 Multi-stage Dockerfile с двумя вариантами

**Файл:** `docker/agent/Dockerfile`

```dockerfile
# ============================================
# Base stage - общая база для обоих образов
# ============================================
FROM php:8.2-cli as base

# Установка системных зависимостей
RUN apt-get update && apt-get install -y \
    openssh-client \
    git \
    unzip \
    libzip-dev \
    && docker-php-ext-install zip \
    && rm -rf /var/lib/apt/lists/*

# Установка Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Создание рабочей директории
WORKDIR /app

# Копирование зависимостей
COPY app/composer.json app/composer.lock ./
RUN composer install --no-dev --no-scripts --no-autoloader

# Копирование исходного кода
COPY app/ ./

# Генерация autoloader
RUN composer dump-autoload --optimize --no-dev

# Копирование entrypoint скрипта
COPY docker/agent/docker-entrypoint.sh /docker-entrypoint.sh
RUN chmod +x /docker-entrypoint.sh

# Создание директории SSH (настройка выполняется в entrypoint)
RUN mkdir -p /root/.ssh && chmod 700 /root/.ssh

# ============================================
# Standalone образ - текущая версия
# Сам получает задачи в цикле
# ============================================
FROM base as standalone

LABEL org.opencontainers.image.title="Agent Standalone"
LABEL org.opencontainers.image.description="Agent that polls tasks independently"
LABEL variant="standalone"

# Entrypoint инициализирует SSH ключ
ENTRYPOINT ["/docker-entrypoint.sh"]

# Точка входа - main.php (текущий Runner)
CMD ["php", "main.php"]

# ============================================
# Orchestrated образ - для оркестратора
# Выполняет одну задачу и завершается
# ============================================
FROM base as orchestrated

LABEL org.opencontainers.image.title="Agent Orchestrated"
LABEL org.opencontainers.image.description="Agent managed by AgentManager orchestrator"
LABEL variant="orchestrated"

# Entrypoint инициализирует SSH ключ
ENTRYPOINT ["/docker-entrypoint.sh"]

# Точка входа - orchestrated.php (новый OrchestratedRunner)
CMD ["php", "orchestrated.php"]
```

### 5.2 Сборка образов

**Создать:** `docker/agent/build.sh`

```bash
#!/bin/bash
set -e

# Версия образов
VERSION=${1:-latest}
REGISTRY=${REGISTRY:-ghcr.io/vasenin26}

echo "Building agent images version: $VERSION"

# Сборка standalone образа
echo "Building standalone image..."
docker build \
  --target standalone \
  -t $REGISTRY/agentmodule:$VERSION-standalone \
  -t $REGISTRY/agentmodule:standalone \
  -f docker/agent/Dockerfile \
  .

# Сборка orchestrated образа
echo "Building orchestrated image..."
docker build \
  --target orchestrated \
  -t $REGISTRY/agentmodule:$VERSION-orchestrated \
  -t $REGISTRY/agentmodule:$VERSION \
  -t $REGISTRY/agentmodule:orchestrated \
  -t $REGISTRY/agentmodule:latest \
  -f docker/agent/Dockerfile \
  .

echo "✅ Build completed!"
echo ""
echo "Standalone image: $REGISTRY/agentmodule:$VERSION-standalone"
echo "Orchestrated image: $REGISTRY/agentmodule:$VERSION-orchestrated (default)"
```

**Сделать исполняемым:**
```bash
chmod +x docker/agent/build.sh
```

### 5.3 Push образов

**Создать:** `docker/agent/push.sh`

```bash
#!/bin/bash
set -e

VERSION=${1:-latest}
REGISTRY=${REGISTRY:-ghcr.io/vasenin26}

echo "Pushing agent images version: $VERSION"

# Push standalone
docker push $REGISTRY/agentmodule:$VERSION-standalone
docker push $REGISTRY/agentmodule:standalone

# Push orchestrated
docker push $REGISTRY/agentmodule:$VERSION-orchestrated
docker push $REGISTRY/agentmodule:$VERSION
docker push $REGISTRY/agentmodule:orchestrated
docker push $REGISTRY/agentmodule:latest

echo "✅ Push completed!"
```

**Сделать исполняемым:**
```bash
chmod +x docker/agent/push.sh
```

### 5.4 Использование образов

#### Standalone образ (текущее поведение)
```bash
# Локальная сборка
docker build --target standalone -t agentmodule:standalone .

# Запуск
docker run --rm \
  -e API_HOST=https://api.example.com \
  -e API_TOKEN=your-token \
  agentmodule:standalone

# Из registry
docker pull ghcr.io/vasenin26/agentmodule:standalone
```

#### Orchestrated образ (для оркестратора)
```bash
# Локальная сборка
docker build --target orchestrated -t agentmodule:orchestrated .

# Запуск (делает оркестратор)
docker run --rm \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=token-abc \
  -e SSH_PRIVATE_KEY="$(cat key.pem)" \
  -e API_HOST=https://api.example.com \
  agentmodule:orchestrated

# Из registry (используется оркестратором)
docker pull ghcr.io/vasenin26/agentmodule:latest
# или
docker pull ghcr.io/vasenin26/agentmodule:orchestrated
```

### 5.5 Docker Compose для локальной разработки

**Создать:** `docker-compose.yml`

```yaml
version: '3.8'

services:
  # Standalone агент (текущее поведение)
  agent-standalone:
    build:
      context: .
      dockerfile: docker/agent/Dockerfile
      target: standalone
    image: agentmodule:standalone
    environment:
      - API_HOST=${API_HOST}
      - API_TOKEN=${API_TOKEN}
      - OPENAI_API_KEY=${OPENAI_API_KEY}
      - OPENAI_MODEL=${OPENAI_MODEL:-gpt-4}
      - GIT_USER_NAME=${GIT_USER_NAME}
      - GIT_USER_EMAIL=${GIT_USER_EMAIL}
    restart: unless-stopped

  # Orchestrated агент (для тестирования)
  agent-orchestrated:
    build:
      context: .
      dockerfile: docker/agent/Dockerfile
      target: orchestrated
    image: agentmodule:orchestrated
    environment:
      - TASK_ID=${TASK_ID:-test-task-123}
      - AGENT_UUID=${AGENT_UUID:-test-uuid-456}
      - AGENT_ID=${AGENT_ID:-test-agent-789}
      - API_TOKEN=${API_TOKEN}
      - SSH_PRIVATE_KEY=${SSH_PRIVATE_KEY}
      - API_HOST=${API_HOST}
      - OPENAI_API_KEY=${OPENAI_API_KEY}
      - OPENAI_MODEL=${OPENAI_MODEL:-gpt-4}
      - GIT_USER_NAME=${GIT_USER_NAME}
      - GIT_USER_EMAIL=${GIT_USER_EMAIL}
    # Для тестирования - не рестартовать автоматически
    restart: "no"
```

### 5.6 GitHub Actions для автоматической сборки

**Создать:** `.github/workflows/build-images.yml`

```yaml
name: Build and Push Agent Images

on:
  push:
    branches: [ main ]
    tags: [ 'v*' ]
  pull_request:
    branches: [ main ]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout
        uses: actions/checkout@v4

      - name: Set up Docker Buildx
        uses: docker/setup-buildx-action@v3

      - name: Log in to Container Registry
        if: github.event_name != 'pull_request'
        uses: docker/login-action@v3
        with:
          registry: ${{ env.REGISTRY }}
          username: ${{ github.actor }}
          password: ${{ secrets.GITHUB_TOKEN }}

      - name: Extract metadata
        id: meta
        uses: docker/metadata-action@v5
        with:
          images: ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}
          tags: |
            type=ref,event=branch
            type=ref,event=pr
            type=semver,pattern={{version}}
            type=semver,pattern={{major}}.{{minor}}
            type=sha

      - name: Build and push Standalone image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: docker/agent/Dockerfile
          target: standalone
          push: ${{ github.event_name != 'pull_request' }}
          tags: |
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:standalone
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ steps.meta.outputs.version }}-standalone
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

      - name: Build and push Orchestrated image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: docker/agent/Dockerfile
          target: orchestrated
          push: ${{ github.event_name != 'pull_request' }}
          tags: |
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:latest
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:orchestrated
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ steps.meta.outputs.version }}
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:${{ steps.meta.outputs.version }}-orchestrated
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max
```

### 5.7 Переменные окружения

#### Standalone образ
```bash
# Обязательные
API_HOST=https://api.example.com
API_TOKEN=your-token

# Опциональные
OPENAI_API_KEY=...
OPENAI_MODEL=gpt-4
GIT_USER_NAME=...
GIT_USER_EMAIL=...
```

#### Orchestrated образ (от оркестратора)
```bash
# Обязательные (устанавливает оркестратор)
TASK_ID=task-123              # ID задачи
AGENT_UUID=uuid-456           # UUID воркера
AGENT_ID=agent-789            # ID агента
API_TOKEN=token-abc           # Токен для API
SSH_PRIVATE_KEY=-----BEGIN... # Приватный ключ ПРОЕКТА
API_HOST=https://api.example.com

# Опциональные
OPENAI_API_KEY=...
OPENAI_MODEL=gpt-4
GIT_USER_NAME=...
GIT_USER_EMAIL=...
```

**Важно:** Все переменные для orchestrated образа устанавливаются оркестратором!

---

## Этап 6: Тестирование (2-3 часа)

### 6.1 Unit тесты

**Создать:** `app/tests/Unit/OrchestratedRunnerTest.php`

```php
<?php

namespace Tests\Unit;

use Anymodule\Agentmodule\OrchestratedRunner;
use PHPUnit\Framework\TestCase;

class OrchestratedRunnerTest extends TestCase
{
    public function testRequiresTaskIdFromEnv(): void
    {
        // Arrange
        putenv('TASK_ID=');
        putenv('AGENT_UUID=test-uuid');
        putenv('AGENT_ID=test-agent');
        
        // Act & Assert
        $this->expectException(\RuntimeException::class);
        // ... test code
    }
    
    public function testRequiresAgentUuidFromEnv(): void
    {
        // Similar test for AGENT_UUID
    }
    
    public function testProcessesTaskSuccessfully(): void
    {
        // Test successful task processing
    }
}
```

### 6.2 Integration тесты

#### Тест Standalone образа

```bash
# Сборка
docker build --target standalone -t agentmodule:standalone .

# Запуск (должен войти в цикл получения задач)
docker run --rm \
  -e API_HOST=https://api.example.com \
  -e API_TOKEN=test-token \
  agentmodule:standalone

# Проверка: агент должен логировать "Getting task.."
# и работать в цикле
```

#### Тест Orchestrated образа

```bash
# Сборка
docker build --target orchestrated -t agentmodule:orchestrated .

# Терминал 1: Запустить mock External API
# (используйте mock из agentmanager/tests/mock/orchestrator_api.py)

# Терминал 2: Запустить контейнер агента с переменными
docker run --rm \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=test-token \
  -e API_HOST=http://host.docker.internal:9000 \
  -e SSH_PRIVATE_KEY="$(cat test_key.pem)" \
  agentmodule:orchestrated

# Проверка: агент должен выполнить одну задачу и завершиться
```

### 6.3 Проверка exit кодов (Orchestrated образ)

```bash
# Успешное выполнение
docker run --rm \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=test-token \
  -e API_HOST=http://api.example.com \
  -e SSH_PRIVATE_KEY="$(cat key.pem)" \
  agentmodule:orchestrated

echo $? # Должно быть 0

# Ошибка (нет TASK_ID)
docker run --rm \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  agentmodule:orchestrated

echo $? # Должно быть 1
```

### 6.4 Тест с docker-compose

```bash
# Тест standalone
docker-compose up agent-standalone

# Тест orchestrated
export TASK_ID=task-123
export AGENT_UUID=uuid-456
export AGENT_ID=agent-789
export SSH_PRIVATE_KEY="$(cat test_key.pem)"
docker-compose up agent-orchestrated
```

---

## 📋 Checklist реализации

### Этап 1: OrchestratedRunner
- [ ] Создать `app/src/OrchestratedRunner.php`
- [ ] Реализовать чтение переменных окружения
- [ ] Реализовать валидацию переменных
- [ ] Реализовать обработку одной задачи
- [ ] Реализовать корректные exit коды (0/1)

### Этап 2: API клиент
- [ ] Добавить метод `getTaskByUuid()` в интерфейс
- [ ] Реализовать `getTaskByUuid()` в Service
- [ ] Реализовать маппинг TaskDTO
- [ ] Добавить логирование
- [ ] Добавить обработку ошибок

### Этап 3: SSH ключи (entrypoint)
- [ ] Создать `docker/agent/docker-entrypoint.sh`
- [ ] Реализовать инициализацию SSH ключа в entrypoint
- [ ] Добавить создание SSH config
- [ ] Добавить логирование в entrypoint
- [ ] Сделать скрипт исполняемым (`chmod +x`)
- [ ] Обновить Git команды (использовать настроенный ключ)
- [ ] Удалить весь код генерации SSH ключей из PHP

### Этап 4: Точки входа
- [ ] Оставить `main.php` без изменений (standalone)
- [ ] Создать `orchestrated.php` (новая точка входа)
- [ ] Интегрировать `OrchestratedRunner` в `orchestrated.php`
- [ ] Убрать переменную `RUN_MODE` (не нужна)

### Этап 5: Docker (два образа)
- [ ] Создать multi-stage Dockerfile
- [ ] Реализовать `base` stage (общая база)
- [ ] Реализовать `standalone` target (текущая версия)
- [ ] Реализовать `orchestrated` target (для оркестратора)
- [ ] Создать скрипт `build.sh`
- [ ] Создать скрипт `push.sh`
- [ ] Создать `docker-compose.yml`
- [ ] Настроить GitHub Actions
- [ ] Протестировать сборку обоих образов

### Этап 6: Тестирование
- [ ] Написать unit тесты
- [ ] Написать integration тесты
- [ ] Протестировать с mock API
- [ ] Протестировать exit коды
- [ ] Протестировать SSH операции

### Этап 7: Документация
- [ ] Обновить README.md
- [ ] Создать ORCHESTRATOR_INTEGRATION.md
- [ ] Документировать переменные окружения
- [ ] Добавить примеры запуска

---

## 🔍 Важные отличия от текущей реализации

### Текущий агент (standalone)
```
Агент запускается → Цикл:
  ├─ Запрашивает задачу из API
  ├─ Генерирует SSH ключи
  ├─ Обрабатывает задачу
  ├─ Повторяет если есть задачи
  └─ Завершается через N попыток
```

### Новый агент (orchestrated)
```
Оркестратор запускает агент с ENV переменными →
  ├─ Читает TASK_ID, AGENT_UUID из ENV
  ├─ Запрашивает детали задачи по UUID
  ├─ Использует SSH ключ проекта из ENV
  ├─ Обрабатывает ОДНУ задачу
  └─ Завершается с exit кодом 0 или 1
```

---

## 📊 Переменные окружения

### Обязательные (от оркестратора)

| Переменная | Источник | Описание | Пример |
|------------|----------|----------|--------|
| `TASK_ID` | Оркестратор | ID задачи для обработки | `task-123` |
| `AGENT_UUID` | Оркестратор | UUID воркера | `uuid-456` |
| `AGENT_ID` | Оркестратор | ID агента | `agent-789` |
| `API_TOKEN` | Оркестратор | Токен для External API | `token-abc` |
| `SSH_PRIVATE_KEY` | Оркестратор | Приватный ключ проекта (PEM) | `-----BEGIN...` |

### Опциональные

| Переменная | По умолчанию | Описание |
|------------|--------------|----------|
| `RUN_MODE` | `orchestrated` | Режим работы: `orchestrated` или `standalone` |
| `API_HOST` | - | Хост External API |

---

## 🧪 Тестовые сценарии

### Сценарий 1: Успешное выполнение задачи

```bash
# Запуск контейнера с корректными переменными
docker run --rm \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=test-token \
  -e API_HOST=https://api.example.com \
  -e SSH_PRIVATE_KEY="$(cat project_key.pem)" \
  agentmodule:latest

# Ожидаемый результат:
# - Лог: "Agent started by orchestrator"
# - Лог: "Fetching task details"
# - Лог: "Task fetched successfully"
# - Лог: "Processing task"
# - Лог: "Task completed successfully"
# - Exit code: 0
```

### Сценарий 2: Отсутствует TASK_ID

```bash
docker run --rm \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  agentmodule:latest

# Ожидаемый результат:
# - Лог: "Missing required environment variables"
# - Exit code: 1
```

### Сценарий 3: Задача не найдена

```bash
docker run --rm \
  -e TASK_ID=non-existent \
  -e AGENT_UUID=invalid-uuid \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=test-token \
  agentmodule:latest

# Ожидаемый результат:
# - Лог: "Failed to fetch task"
# - Exit code: 1
```

### Сценарий 4: Ошибка обработки задачи

```bash
# Запуск с корректными переменными, но задача выбрасывает исключение

# Ожидаемый результат:
# - Лог: "Task processing failed"
# - Лог со stack trace исключения
# - Exit code: 1
```

---

## 🔄 Обратная совместимость

### Сохранение старого режима

Для локальной разработки и тестирования можно оставить старый `Runner`:

**Создать:** `app/legacy_main.php`

```php
<?php
// Скопировать содержимое старого main.php
// Используется когда RUN_MODE=standalone

use Anymodule\Agentmodule\Runner;
// ... existing code

(new Runner($api, StateStore::run(), $processorFactory))->run();
```

**Запуск в standalone режиме:**
```bash
docker run -e RUN_MODE=standalone agentmodule:latest
```

---

## 📝 Документация для пользователей

### Создать файл `ORCHESTRATOR_INTEGRATION.md`

```markdown
# Интеграция с AgentManager Orchestrator

## Обзор

Этот агент работает под управлением AgentManager оркестратора.

## Режимы работы

### Orchestrated Mode (по умолчанию)
Агент запускается оркестратором для обработки одной конкретной задачи.

### Standalone Mode (legacy)
Агент работает в автономном режиме, самостоятельно получая задачи.

## Переменные окружения

### Обязательные (orchestrated mode)
- `TASK_ID` - ID задачи
- `AGENT_UUID` - UUID воркера
- `AGENT_ID` - ID агента
- `API_TOKEN` - токен для API
- `SSH_PRIVATE_KEY` - SSH ключ проекта

### Опциональные
- `RUN_MODE=orchestrated|standalone` (default: orchestrated)

## Запуск

### Под управлением оркестратора
Оркестратор автоматически запускает контейнер с необходимыми переменными.

### Ручной запуск (для тестирования)
```bash
docker run --rm \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  ... \
  agentmodule:latest
```

## Exit коды
- `0` - Задача выполнена успешно
- `1` - Ошибка выполнения

## SSH ключи
Агент использует SSH ключ проекта, предоставленный оркестратором.
Агент НЕ генерирует собственные SSH ключи.
```

---

## ⚠️ Критические моменты

### 1. SSH ключи генерируются ОРКЕСТРАТОРОМ и инициализируются в entrypoint!

```
❌ НЕ ДЕЛАТЬ в PHP коде:
- Генерировать SSH ключи в PHP
- Сохранять SSH ключи в постоянное хранилище
- Отправлять публичный ключ в GitHub
- Создавать/удалять временные файлы
- Инициализировать SSH ключ в PHP коде

✅ ДЕЛАТЬ:
- Создать docker-entrypoint.sh для инициализации SSH
- Читать SSH_PRIVATE_KEY из ENV в entrypoint скрипте
- Сохранять в /root/.ssh/project_key в entrypoint (до запуска PHP)
- Настраивать SSH config в entrypoint
- В PHP просто использовать git команды - ключ уже настроен!
```

### 2. Агент обрабатывает ОДНУ задачу

```
❌ НЕ ДЕЛАТЬ:
- Цикл while(true) для получения задач
- Повторные попытки получения задач
- Sleep между попытками

✅ ДЕЛАТЬ:
- Получить TASK_ID из ENV
- Запросить задачу один раз
- Обработать задачу
- Завершиться
```

### 3. Корректные exit коды

```
✅ Exit 0 - задача выполнена успешно
✅ Exit 1 - любая ошибка

Оркестратор отслеживает exit код для:
- Освобождения ресурсов
- Запуска следующей задачи из очереди
- Метрик и мониторинга
```

---

## 📊 Архитектура после изменений

### Жизненный цикл контейнера (orchestrated)

```
┌─────────────────────────────────────────────────────────────────────┐
│  Docker Run                                                         │
│  ENV: TASK_ID, AGENT_UUID, SSH_PRIVATE_KEY, API_HOST, API_TOKEN   │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  ENTRYPOINT: /docker-entrypoint.sh                                  │
│    ├─ Инициализация SSH ключа                                       │
│    │   └─ Сохраняет SSH_PRIVATE_KEY в /root/.ssh/project_key       │
│    ├─ Создание SSH config                                           │
│    └─ Логирование режима работы                                     │
└────────────────────────────┬────────────────────────────────────────┘
                             │
                             ▼
┌─────────────────────────────────────────────────────────────────────┐
│  CMD: php orchestrated.php                                          │
│                                                                     │
│  OrchestratedRunner::run()                                          │
│    ├─ Читает TASK_ID, AGENT_UUID из ENV                            │
│    ├─ Получает задачу через API (getTaskByUuid)                    │
│    ├─ Обрабатывает ОДНУ задачу                                      │
│    └─ Exit 0 (success) или Exit 1 (error)                          │
└─────────────────────────────────────────────────────────────────────┘
```

### Сравнение двух образов

| Аспект | Standalone | Orchestrated |
|--------|-----------|--------------|
| **Entrypoint** | `/docker-entrypoint.sh` | `/docker-entrypoint.sh` |
| **CMD** | `php main.php` | `php orchestrated.php` |
| **Runner класс** | `Runner` | `OrchestratedRunner` |
| **Получение задач** | Цикл `while(true)` | Из ENV переменных |
| **Количество задач** | Бесконечно | Одна задача |
| **SSH ключи** | Генерирует сам (legacy) | Получает от оркестратора |
| **SSH инициализация** | В entrypoint (опционально) | В entrypoint (обязательно) |
| **Exit code** | Не завершается | 0 = success, 1 = error |
| **Переменные ENV** | `API_HOST`, `API_TOKEN` | `TASK_ID`, `AGENT_UUID`, `SSH_PRIVATE_KEY` + базовые |
| **Использование** | Локальная разработка | Управление оркестратором |
| **Docker tag** | `:standalone` | `:latest`, `:orchestrated` |
| **Restart policy** | `unless-stopped` | `no` (one-shot) |

## 🚀 Порядок реализации

### День 1: Основа и API (3-4 часа)
1. ✅ Создать `OrchestratedRunner.php` - 1 час
2. ✅ Реализовать чтение ENV переменных (TASK_ID, AGENT_UUID)
3. ✅ Добавить метод `getTaskByUuid()` в API - 30 минут
4. ✅ Создать `orchestrated.php` (точка входа) - 30 минут
5. ✅ Unit тесты для `OrchestratedRunner` - 1 час

### День 2: SSH и Docker entrypoint (2-3 часа)
6. ✅ Создать `docker/agent/docker-entrypoint.sh` - 30 минут
7. ✅ Реализовать инициализацию SSH в entrypoint - 30 минут
8. ✅ Удалить код генерации SSH ключей из PHP - 30 минут
9. ✅ Обновить Git команды (убрать временные файлы) - 30 минут
10. ✅ Тестирование entrypoint локально - 30 минут

### День 3: Docker образы (3-4 часа)
11. ✅ Создать multi-stage Dockerfile (`base`, `standalone`, `orchestrated`) - 1 час
12. ✅ Добавить ENTRYPOINT в Dockerfile - 15 минут
13. ✅ Создать скрипты `build.sh` и `push.sh` - 30 минут
14. ✅ Создать `docker-compose.yml` для тестирования - 30 минут
15. ✅ Протестировать сборку обоих образов - 1 час

### День 4: CI/CD и тестирование (3-4 часа)
16. ✅ Настроить GitHub Actions для автоматической сборки - 1 час
17. ✅ Integration тесты standalone образа - 1 час
18. ✅ Integration тесты orchestrated образа - 1 час
19. ✅ Тестирование exit кодов - 30 минут

### День 5: Документация и финализация (2-3 часа)
20. ✅ Обновить README.md - 30 минут
21. ✅ Создать ORCHESTRATOR_INTEGRATION.md - 1 час
22. ✅ Code review - 30 минут
23. ✅ Финальное интеграционное тестирование - 30 минут

**Общее время:** 3-4 дня разработки (13-18 часов)

**Упрощения благодаря entrypoint подходу:**
- ✅ Нет PHP класса `SshKeyInitializer` - инициализация в bash
- ✅ Нет переменной `RUN_MODE` - явные точки входа
- ✅ `main.php` остается без изменений - чистая архитектура

---

## 📚 Справочные материалы

### Документация оркестратора
- `agentmanager/QUICK_START.md` - быстрый старт
- `agentmanager/docs/task-api-specification.md` - спецификация API
- `agentmanager/docs/ssh-keys-architecture.md` - архитектура SSH ключей
- `agentmanager/docs/orchestrator-configuration.md` - конфигурация

### External API endpoints
- `POST /api/agent/task` - получение задачи по agent_uuid
- `PUT /api/agent/task/{id}` - обновление/завершение задачи

---

## ✅ Критерии приемки

### Функциональность
- [ ] Агент читает TASK_ID, AGENT_UUID из ENV
- [ ] Агент запрашивает задачу через POST /api/agent/task
- [ ] Агент использует SSH ключ проекта из ENV
- [ ] Агент обрабатывает одну задачу и завершается
- [ ] Exit code 0 при успехе, 1 при ошибке

### Безопасность
- [ ] SSH ключ сохраняется во временный файл с правами 600
- [ ] Временный файл удаляется после использования
- [ ] Приватный ключ не логируется

### Качество кода
- [ ] Unit тесты покрывают основную логику
- [ ] Integration тесты проверяют работу с API
- [ ] Код соответствует PSR стандартам
- [ ] Документация актуальна

### Docker
- [ ] Образ собирается без ошибок
- [ ] Контейнер запускается с переменными окружения
- [ ] Корректные exit коды
- [ ] SSH клиент установлен и настроен

---

## 📦 Краткая справка по использованию образов

### Сборка образов

```bash
# Сборка обоих образов
./docker/agent/build.sh v1.0.0

# Или вручную
docker build --target standalone -t agentmodule:standalone .
docker build --target orchestrated -t agentmodule:orchestrated .
```

### Запуск Standalone образа

```bash
# Из локальной сборки
docker run -d \
  --name agent-standalone \
  --restart unless-stopped \
  -e API_HOST=https://api.example.com \
  -e API_TOKEN=your-token \
  agentmodule:standalone

# Из registry
docker run -d \
  --name agent-standalone \
  --restart unless-stopped \
  -e API_HOST=https://api.example.com \
  -e API_TOKEN=your-token \
  ghcr.io/vasenin26/agentmodule:standalone
```

### Запуск Orchestrated образа (для оркестратора)

```bash
# Оркестратор запускает так:
# Entrypoint автоматически инициализирует SSH ключ!
docker run --rm \
  --name agent-task-123 \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=token-abc \
  -e SSH_PRIVATE_KEY="-----BEGIN RSA PRIVATE KEY-----..." \
  -e API_HOST=https://api.example.com \
  ghcr.io/vasenin26/agentmodule:latest

# Exit code 0 = success
# Exit code 1 = error
```

### Конфигурация оркестратора

В AgentManager нужно указать образ:

```yaml
# docker-compose.prod.yaml (agentmanager)
services:
  agent-svc:
    # ...
    environment:
      # Образ агента для orchestrated режима
      - AGENT_IMAGE=ghcr.io/vasenin26/agentmodule:latest
      # или
      # - AGENT_IMAGE=ghcr.io/vasenin26/agentmodule:orchestrated
```

### Push в registry

```bash
# Push обоих образов
./docker/agent/push.sh v1.0.0

# Или через GitHub Actions (автоматически при push тега)
git tag v1.0.0
git push origin v1.0.0
```

---

## 🎉 Заключение

После реализации этого плана у вас будет:

### ✅ Два полноценных Docker образа

**Standalone образ** - для автономной работы:
- ✅ Сам получает задачи из API
- ✅ Работает в бесконечном цикле
- ✅ Генерирует собственные SSH ключи
- ✅ Используется для локальной разработки

**Orchestrated образ** - для оркестратора:
- ✅ Получает задачу через ENV переменные
- ✅ Использует SSH ключи проекта от оркестратора
- ✅ Обрабатывает одну задачу и завершается
- ✅ Корректные exit коды (0/1)
- ✅ Полная совместимость с AgentManager

### ✅ Гибкость и масштабируемость

- 🔄 Обратная совместимость (старый код работает)
- 🚀 Готовность к масштабированию через оркестратор
- 🔧 Возможность локальной разработки (standalone)
- 📦 Автоматическая сборка через GitHub Actions
- 🎯 Один codebase - два режима работы

**Следующий шаг:** Начните с Этапа 1 - создание `OrchestratedRunner.php`

---

**Дата создания:** 2025-10-06  
**Версия:** 1.0  
**Статус:** ✅ Готов к реализации

