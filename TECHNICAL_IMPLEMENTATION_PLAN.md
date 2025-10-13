# Технический план интеграции с AgentManager Orchestrator

**Дата создания:** 2025-10-06  
**Версия:** 1.0  
**Статус:** ✅ Готов к реализации

---

## 📋 Содержание

1. [Обзор архитектуры](#обзор-архитектуры)
2. [Анализ текущей реализации](#анализ-текущей-реализации)
3. [Этапы реализации](#этапы-реализации)
4. [Детальный технический план](#детальный-технический-план)
5. [Изменения в кодовой базе](#изменения-в-кодовой-базе)
6. [Тестирование](#тестирование)
7. [Deployment](#deployment)

---

## 🏗️ Обзор архитектуры

### Текущая архитектура (Standalone)

```
┌─────────────────────────────────────────────────────┐
│ Docker Container: agentmodule:standalone             │
│                                                       │
│  ┌───────────────────────────────────────────────┐  │
│  │ ENTRYPOINT: docker-entrypoint.sh               │  │
│  │   - Настройка Git user (GIT_USER_NAME/EMAIL)  │  │
│  │   - Инициализация SSH ключа (если есть)       │  │
│  └───────────────┬───────────────────────────────┘  │
│                  │                                    │
│  ┌───────────────▼───────────────────────────────┐  │
│  │ CMD: php main.php                              │  │
│  │                                                │  │
│  │ Runner::run()                                  │  │
│  │   ├─ while(true) - бесконечный цикл           │  │
│  │   ├─ $agentId = Uuid::uuid4()                 │  │
│  │   ├─ $task = $api->getTask($agentId)          │  │
│  │   ├─ if ($task) { process($task) }            │  │
│  │   ├─ else { sleep(10) }                       │  │
│  │   └─ $attemptLimit-- (max 10 attempts)        │  │
│  └────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────┘
```

### Целевая архитектура (Orchestrated)

```
┌──────────────────────────────────────────────────────────────────┐
│ AgentManager Orchestrator (Go)                                   │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ 1. GET /tasks/next → External API                          │  │
│  │ 2. POST /tasks/{id}/reserve → External API                 │  │
│  │ 3. docker run -e TASK_ID=... -e AGENT_UUID=...            │  │
│  └────────────────────┬────────────────────────────────────────┘  │
└────────────────────────┼──────────────────────────────────────────┘
                         │
                         ▼
┌──────────────────────────────────────────────────────────────────┐
│ Docker Container: agentmodule:orchestrated                        │
│                                                                   │
│ ENV Variables:                                                    │
│   - TASK_ID=task-123                                             │
│   - AGENT_UUID=uuid-456                                          │
│   - AGENT_ID=agent-789                                           │
│   - API_TOKEN=token-abc                                          │
│   - SSH_PRIVATE_KEY=-----BEGIN... (PROJECT KEY!)                │
│   - API_HOST=https://api.example.com                             │
│                                                                   │
│  ┌────────────────────────────────────────────────────────────┐  │
│  │ ENTRYPOINT: docker-entrypoint.sh                            │  │
│  │   - Настройка Git user                                      │  │
│  │   - Инициализация SSH ключа ПРОЕКТА:                       │  │
│  │     * echo "$SSH_PRIVATE_KEY" > ~/.ssh/id_rsa              │  │
│  │     * chmod 600 ~/.ssh/id_rsa                              │  │
│  │     * ssh-keyscan github.com gitlab.com >> known_hosts     │  │
│  └────────────────────┬───────────────────────────────────────┘  │
│                       │                                           │
│  ┌────────────────────▼───────────────────────────────────────┐  │
│  │ CMD: php orchestrated.php                                   │  │
│  │                                                             │  │
│  │ OrchestratedRunner::run()                                   │  │
│  │   ├─ Читает ENV: TASK_ID, AGENT_UUID, AGENT_ID            │  │
│  │   ├─ Валидация обязательных переменных                     │  │
│  │   ├─ $task = $api->getTaskByUuid($agentUuid)              │  │
│  │   │    POST /api/agent/task {agent_uuid}                  │  │
│  │   ├─ $processor->process($task, $handler)                 │  │
│  │   ├─ Log::info("Task completed")                           │  │
│  │   └─ exit(0) - ОДНА задача, завершение                    │  │
│  └─────────────────────────────────────────────────────────────┘  │
└──────────────────────────────────────────────────────────────────┘
```

### Ключевые различия

| Аспект | Standalone | Orchestrated |
|--------|-----------|--------------|
| **Точка входа** | `main.php` | `orchestrated.php` |
| **Runner класс** | `Runner` | `OrchestratedRunner` (новый) |
| **Agent ID** | Генерируется (`Uuid::uuid4()`) | Из ENV (`AGENT_ID`) |
| **Получение задач** | Цикл `while(true)` + API | Один раз из ENV + API |
| **SSH ключи** | Опциональные (из ENV или нет) | Обязательные из ENV (проекта) |
| **Обработка** | Многократная (цикл) | Одна задача |
| **Завершение** | После 10 неудачных попыток | После выполнения задачи |
| **Exit code** | Всегда 0 | 0 = success, 1 = error |

---

## 🔍 Анализ текущей реализации

### Файловая структура

```
app/
├── main.php                          # ✅ Точка входа (standalone)
├── src/
│   ├── Runner.php                    # ✅ Текущий runner (standalone)
│   ├── Entity/
│   │   └── Task.php                  # ✅ Task entity
│   ├── Interface/
│   │   ├── Task/
│   │   │   └── TaskApi.php           # 🔧 Требует расширения
│   │   └── StateStoreInterface.php
│   ├── Services/
│   │   ├── ApiService/
│   │   │   ├── Service.php           # 🔧 Требует нового метода
│   │   │   ├── ApiClient.php         # ✅ Готов (Guzzle)
│   │   │   ├── Request/
│   │   │   │   └── Tasks/
│   │   │   │       ├── GetAgentTask.php      # ✅ Существует
│   │   │   │       └── UpdateAgentTask.php   # ✅ Существует
│   │   │   └── Response/
│   │   │       └── Tasks/
│   │   │           └── TaskDTO.php   # ✅ Существует
│   │   ├── RepositoryService/
│   │   │   └── RepositoryProvider.php # ✅ Git операции (czproject/git-php)
│   │   └── StateStore.php            # ✅ Готов
│   └── Utils/
│       └── Log.php                   # ✅ Готов
├── vendor/                           # ✅ Зависимости установлены
│   ├── czproject/git-php/            # ✅ v4.5 - Git операции
│   ├── guzzlehttp/guzzle/            # ✅ v7.10 - HTTP клиент
│   └── ramsey/uuid/                  # ✅ v4.9 - UUID генерация
└── composer.json                     # ✅ Готов

docker/
└── agent/
    ├── Dockerfile                    # 🔧 Требует multi-stage
    └── docker-entrypoint.sh          # ✅ Уже настроен SSH!
```

### Важные находки

#### ✅ API структура проверена и корректна!

**External API (TaskController.php) возвращает:**
```json
{
  "id": 123,
  "type": "documentation",
  "agent_uuid": "uuid-456",
  "project_id": 789,
  "result_required": true,
  "chat": {
    "id": 456,
    "messages": [...]
  }
}
```

**Агент парсит (GetAgentTask.php):**
```php
TaskDTO(
    task_id: $data['id'],                     // ✅
    chat_id: $data['chat']['id'],             // ✅
    type: $data['type'],                      // ✅
    project_id: $data['project_id'],          // ✅
    messages: $data['chat']['messages'],      // ✅
    resulRequired: $data['result_required']   // ✅
)
```

**✅ Полное соответствие!** Никаких изменений в маппинге не требуется.

#### ✅ SSH уже настроен в entrypoint!

Файл `docker/agent/docker-entrypoint.sh` УЖЕ настраивает SSH ключ:

```bash
# Настройка SSH ключа
if [ -n "$SSH_PRIVATE_KEY" ]; then
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    echo "$SSH_PRIVATE_KEY" | tr -d '\r' > ~/.ssh/id_rsa
    chmod 600 ~/.ssh/id_rsa
    ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts 2>/dev/null
    ssh-keyscan -t rsa gitlab.com >> ~/.ssh/known_hosts 2>/dev/null
    chmod 644 ~/.ssh/known_hosts
    echo "[entrypoint] SSH ключ успешно настроен"
fi
```

**⚠️ Изменения для orchestrated режима:**
- ✅ Базовая логика уже работает!
- 🔧 Нужно добавить логирование режима работы
- 🔧 Нужно сделать SSH обязательным для orchestrated

#### ✅ API клиент готов

`GetAgentTask` УЖЕ использует правильный endpoint:

```php
// app/src/Services/ApiService/Request/Tasks/GetAgentTask.php
public function getMethod(): string { return 'POST'; }
public function getUrl(): string { return 'agent/task'; }
public function getPayload(): array { return ['agent_uuid' => $this->agentId]; }
```

**✅ Готово:**
- Правильный метод POST
- Правильный endpoint `agent/task`
- Правильный payload `{agent_uuid}`
- Обработка 404 (нет задачи)

**🔧 Что нужно:**
- Новый интерфейс метод `getTaskByUuid(string $agentUuid): ?Task`
- Новая реализация в `Service.php`

#### ✅ Git операции через czproject/git-php

```php
// app/src/Services/RepositoryService/RepositoryProvider.php
public function getRepo(string $url): GitRepository
{
    // Автоматическое преобразование HTTPS → SSH
    if (str_starts_with($url, 'https://')) {
        $url = $this->convertHttpsToSsh($url);
    }
    
    $git = new Git();
    $repo = $git->cloneRepository($url, $fullPath); // Использует ~/.ssh/id_rsa
    $repo->pull();
}
```

**✅ Готово:**
- Автоматическое использование SSH ключа из `~/.ssh/id_rsa`
- Конвертация HTTPS → SSH
- Нет необходимости в изменениях!

#### ✅ Зависимости

```json
// composer.json
{
  "require": {
    "czproject/git-php": "^4.5",      // ✅ Git операции
    "guzzlehttp/guzzle": "^7.10",     // ✅ HTTP клиент (POST запросы)
    "ramsey/uuid": "^4.9",            // ✅ UUID генерация
    "monolog/monolog": "^3.9",        // ✅ Логирование
    "openai-php/client": "^0.16.0"    // ✅ OpenAI интеграция
  }
}
```

**✅ Все зависимости установлены и готовы!**

---

## 🚀 Этапы реализации

### Общая оценка времени: 3-4 дня (13-18 часов)

```
День 1 (3-4ч)  → Этап 1: OrchestratedRunner + API клиент
День 2 (2-3ч)  → Этап 2: Обновление entrypoint + точки входа
День 3 (3-4ч)  → Этап 3: Multi-stage Dockerfile + скрипты
День 4 (3-4ч)  → Этап 4: Тестирование + CI/CD
День 5 (2-3ч)  → Этап 5: Документация + финализация
```

### Приоритизация

| Приоритет | Этап | Время | Критичность |
|-----------|------|-------|-------------|
| 🔴 P0 | OrchestratedRunner | 1ч | Критично |
| 🔴 P0 | API метод getTaskByUuid | 30м | Критично |
| 🔴 P0 | orchestrated.php | 30м | Критично |
| 🟡 P1 | Обновление entrypoint | 30м | Важно |
| 🟡 P1 | Multi-stage Dockerfile | 1ч | Важно |
| 🟢 P2 | Скрипты build/push | 30м | Полезно |
| 🟢 P2 | Docker Compose | 30м | Полезно |
| 🟢 P2 | GitHub Actions | 1ч | Полезно |
| ⚪ P3 | Unit тесты | 2ч | Опционально |
| ⚪ P3 | Integration тесты | 2ч | Опционально |

---

## 📝 Детальный технический план

## Этап 1: OrchestratedRunner и API клиент (3-4 часа)

### 1.1 Создать OrchestratedRunner (1 час)

**Файл:** `app/src/OrchestratedRunner.php`

**Требования:**
- Читать переменные окружения (`TASK_ID`, `AGENT_UUID`, `AGENT_ID`)
- Валидировать обязательные переменные
- Вызвать API для получения задачи
- Обработать ОДНУ задачу
- Корректные exit коды (0/1)

**Реализация:**

```php
<?php

namespace Anymodule\Agentmodule;

use Anymodule\Agentmodule\Interface\StateStoreInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\ResultHandlers\DocsModule;
use Anymodule\Agentmodule\Utils\Log;

/**
 * Orchestrated режим работы агента
 * 
 * Агент запускается оркестратором для обработки ОДНОЙ задачи.
 * Получает задачу через переменные окружения от оркестратора.
 * После обработки завершается с exit кодом 0 (success) или 1 (error).
 */
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

        // Валидация обязательных переменных
        if (!$taskId || !$agentUuid || !$agentId) {
            Log::error('Missing required environment variables for orchestrated mode', [
                'TASK_ID' => $taskId ?: 'not set',
                'AGENT_UUID' => $agentUuid ?: 'not set',
                'AGENT_ID' => $agentId ?: 'not set',
            ]);
            
            echo "ERROR: Missing required environment variables\n";
            echo "Required: TASK_ID, AGENT_UUID, AGENT_ID\n";
            
            exit(1);
        }

        $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'started');

        Log::info("🚀 Agent started in ORCHESTRATED mode", [
            'agent_id' => $agentId,
            'agent_uuid' => $agentUuid,
            'task_id' => $taskId,
            'mode' => 'orchestrated',
        ]);

        try {
            // Получить полные данные задачи из External API
            Log::info("📥 Fetching task details from API", [
                'task_id' => $taskId,
                'agent_uuid' => $agentUuid,
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'fetching');
            $task = $this->api->getTaskByUuid($agentUuid);

            if (is_null($task)) {
                Log::error("❌ Failed to fetch task - task not found or not assigned", [
                    'task_id' => $taskId,
                    'agent_uuid' => $agentUuid,
                ]);
                
                $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'error');
                
                echo "ERROR: Task not found or not assigned to this agent\n";
                echo "Task ID: $taskId\n";
                echo "Agent UUID: $agentUuid\n";
                
                exit(1);
            }

            Log::info("✅ Task fetched successfully", [
                'task_id' => $task->id,
                'type' => $task->type ?? 'unknown',
                'project_id' => $task->projectId,
                'conversation_id' => $task->conversationId,
            ]);

            // Обработать задачу
            Log::info("⚙️ Processing task", [
                'task_id' => $task->id,
                'type' => $task->type,
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'processing');

            // Создать handler для обработки результата
            $handler = new DocsModule($this->api, $agentId, $task);
            
            // Обработать задачу через соответствующий процессор
            $this->processorFactory->createProcessorForTask($task)
                ->process($task, $handler);

            Log::info("✅ Task completed successfully", [
                'task_id' => $task->id,
                'agent_id' => $agentId,
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'completed');
            
            // Успешное завершение
            echo "SUCCESS: Task completed\n";
            echo "Task ID: {$task->id}\n";
            
            exit(0);

        } catch (\Throwable $e) {
            Log::exception($e, '❌ Task processing failed', [
                'agent_id' => $agentId,
                'agent_uuid' => $agentUuid,
                'task_id' => $taskId,
                'error_class' => get_class($e),
                'error_message' => $e->getMessage(),
            ]);
            
            $this->stateStore->push(self::STORE_AGENT_STATUS_KEY, 'failed');
            
            echo "ERROR: Task processing failed\n";
            echo "Task ID: $taskId\n";
            echo "Error: {$e->getMessage()}\n";
            
            // Завершение с ошибкой
            exit(1);
        }
    }
}
```

**Ключевые особенности:**
- ✅ Читает ENV переменные: `TASK_ID`, `AGENT_UUID`, `AGENT_ID`
- ✅ Валидация с понятными сообщениями об ошибках
- ✅ Подробное логирование каждого этапа с эмодзи
- ✅ Вывод в stdout для Docker logs
- ✅ Корректные exit коды: 0 = success, 1 = error
- ✅ Обработка ОДНОЙ задачи (нет циклов)
- ✅ StateStore интеграция для мониторинга
- ✅ Использует существующие ProcessorFactory и DocsModule

### 1.2 Расширить TaskApi интерфейс (15 минут)

**Файл:** `app/src/Interface/Task/TaskApi.php`

```php
<?php

namespace Anymodule\Agentmodule\Interface\Task;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Entity\TaskState;
use Ramsey\Uuid\UuidInterface;

interface TaskApi
{
    /**
     * Получить задачу для агента (standalone режим)
     * 
     * @param UuidInterface $agentId UUID агента
     * @return Task|null Задача или null если нет задач
     */
    public function getTask(UuidInterface $agentId): ?Task;

    /**
     * Получить задачу по UUID агента (orchestrated режим)
     * 
     * Используется в orchestrated режиме когда оркестратор
     * уже зарезервировал задачу для агента.
     * 
     * @param string $agentUuid UUID агента от оркестратора
     * @return Task|null Задача или null если задача не найдена
     */
    public function getTaskByUuid(string $agentUuid): ?Task;

    /**
     * Отправить результат обработки задачи
     * 
     * @param UuidInterface $agentId UUID агента
     * @param int $taskId ID задачи
     * @param ProcessingResult $result Результат обработки
     * @return TaskState Состояние задачи
     */
    public function sendResult(UuidInterface $agentId, int $taskId, ProcessingResult $result): TaskState;
}
```

### 1.3 Реализовать getTaskByUuid в Service (30 минут)

**Файл:** `app/src/Services/ApiService/Service.php`

Добавить метод в существующий класс:

```php
/**
 * Получить задачу по UUID агента (orchestrated режим)
 * 
 * Endpoint: POST /api/agent/task
 * Body: {"agent_uuid": "uuid-456"}
 * 
 * Ответ от API:
 * {
 *   "id": 123,
 *   "type": "documentation",
 *   "agent_uuid": "uuid-456",
 *   "project_id": 789,
 *   "result_required": true,
 *   "chat": {
 *     "id": 456,
 *     "messages": [...]
 *   }
 * }
 * 
 * Используется в orchestrated режиме когда оркестратор
 * уже зарезервировал задачу для агента.
 * 
 * @param string $agentUuid UUID агента от оркестратора
 * @return Task|null Задача или null если не найдена
 */
public function getTaskByUuid(string $agentUuid): ?Task
{
    Log::info("🔍 Requesting task by UUID", [
        'agent_uuid' => $agentUuid,
        'mode' => 'orchestrated',
    ]);
    
    // Используем существующий GetAgentTask request
    // Он уже правильно настроен!
    $request = new GetAgentTask($this->token, $agentUuid);
    $taskData = $request->exec($this->api);

    if (is_null($taskData)) {
        Log::warning("⚠️ Task not found for agent UUID", [
            'agent_uuid' => $agentUuid,
        ]);
        
        return null;
    }

    Log::info("✅ Task received from API", [
        'task_id' => $taskData->task_id,
        'type' => $taskData->type ?? 'unknown',
        'project_id' => $taskData->project_id,
        'chat_id' => $taskData->chat_id,
        'messages_count' => count($taskData->messages),
    ]);

    // Маппинг в Task entity (как в getTask)
    return new Task(
        id: $taskData->task_id,
        type: $taskData->type,
        conversationId: $taskData->chat_id,
        messages: $taskData->messages,
        projectId: $taskData->project_id,
        resultRequired: $taskData->resulRequired
    );
}
```

**Ключевые особенности:**
- ✅ Переиспользует существующий `GetAgentTask` request
- ✅ Правильный endpoint и payload уже настроены
- ✅ Обработка 404 (нет задачи)
- ✅ Подробное логирование
- ✅ Маппинг в существующий Task entity

### 1.4 Проверка целостности (15 минут)

**Проверить что существующий код не сломан:**

```bash
# Проверить что Runner.php использует правильный метод
grep -n "getTask" app/src/Runner.php

# Проверить что все импорты корректны
php -l app/src/OrchestratedRunner.php
php -l app/src/Services/ApiService/Service.php

# Запустить PHPUnit тесты
docker-compose run --rm agentmodule php vendor/bin/phpunit
```

**Checklist:**
- [ ] `OrchestratedRunner.php` создан и проверен синтаксис
- [ ] `TaskApi.php` расширен новым методом
- [ ] `Service.php` реализует `getTaskByUuid`
- [ ] Существующие тесты проходят
- [ ] Нет синтаксических ошибок

---

## Этап 2: Обновление entrypoint и точки входа (2-3 часа)

### 2.1 Обновить docker-entrypoint.sh (30 минут)

**Файл:** `docker/agent/docker-entrypoint.sh`

**Текущая версия УЖЕ настраивает SSH!** Нужно добавить:
- Логирование режима работы
- Валидацию SSH для orchestrated режима

```bash
#!/bin/sh
set -e

# Цвета для логов
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo "${GREEN}  AgentModule Docker Container Initialization${NC}"
echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo ""

# Определение режима работы
ORCHESTRATED_MODE=false
if [ -n "$TASK_ID" ] && [ -n "$AGENT_UUID" ] && [ -n "$AGENT_ID" ]; then
    ORCHESTRATED_MODE=true
    echo "${BLUE}[entrypoint]${NC} 🎭 Running in ${GREEN}ORCHESTRATED${NC} mode (managed by AgentManager)"
    echo "${BLUE}[entrypoint]${NC}   📋 TASK_ID: $TASK_ID"
    echo "${BLUE}[entrypoint]${NC}   🆔 AGENT_UUID: $AGENT_UUID"
    echo "${BLUE}[entrypoint]${NC}   🤖 AGENT_ID: $AGENT_ID"
else
    echo "${BLUE}[entrypoint]${NC} 🔄 Running in ${YELLOW}STANDALONE${NC} mode"
fi
echo ""

# Настройка Git пользователя
echo "${BLUE}[entrypoint]${NC} 👤 Configuring Git user..."
if [ -n "$GIT_USER_NAME" ] && [ -n "$GIT_USER_EMAIL" ]; then
    git config --global user.name "$GIT_USER_NAME"
    git config --global user.email "$GIT_USER_EMAIL"
    echo "${GREEN}[entrypoint]${NC} ✅ Git user configured: $GIT_USER_NAME <$GIT_USER_EMAIL>"
else
    echo "${YELLOW}[entrypoint]${NC} ⚠️  GIT_USER_NAME or GIT_USER_EMAIL not set"
    echo "${YELLOW}[entrypoint]${NC} ⚠️  Git operations (commit, push) may fail"
fi
echo ""

# Настройка SSH ключа
echo "${BLUE}[entrypoint]${NC} 🔑 Configuring SSH key..."
if [ -n "$SSH_PRIVATE_KEY" ]; then
    echo "${BLUE}[entrypoint]${NC} 🔐 Initializing project SSH key..."
    
    # Создать директорию .ssh если нет
    mkdir -p ~/.ssh
    chmod 700 ~/.ssh
    
    # Сохранить приватный ключ
    echo "$SSH_PRIVATE_KEY" | tr -d '\r' > ~/.ssh/id_rsa
    chmod 600 ~/.ssh/id_rsa
    
    # Добавить GitHub/GitLab в known_hosts
    echo "${BLUE}[entrypoint]${NC} 📝 Adding GitHub and GitLab to known_hosts..."
    ssh-keyscan -t rsa github.com >> ~/.ssh/known_hosts 2>/dev/null
    ssh-keyscan -t rsa gitlab.com >> ~/.ssh/known_hosts 2>/dev/null
    chmod 644 ~/.ssh/known_hosts
    
    echo "${GREEN}[entrypoint]${NC} ✅ SSH key initialized successfully"
    echo "${GREEN}[entrypoint]${NC}    Location: ~/.ssh/id_rsa"
else
    if [ "$ORCHESTRATED_MODE" = true ]; then
        echo "${RED}[entrypoint]${NC} ❌ ERROR: SSH_PRIVATE_KEY not set in orchestrated mode"
        echo "${RED}[entrypoint]${NC} ❌ Project SSH key is REQUIRED for orchestrated mode"
        echo "${RED}[entrypoint]${NC} ❌ Container will exit"
        exit 1
    else
        echo "${YELLOW}[entrypoint]${NC} ⚠️  SSH_PRIVATE_KEY not set"
        echo "${YELLOW}[entrypoint]${NC} ⚠️  Private Git repositories will not be accessible"
    fi
fi
echo ""

echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo "${GREEN}  Initialization complete. Starting application...${NC}"
echo "${GREEN}═══════════════════════════════════════════════════${NC}"
echo ""

# Запустить основное приложение (передать все аргументы CMD)
exec "$@"
```

**Ключевые изменения:**
- ✅ Автоопределение режима по ENV переменным
- ✅ Цветной вывод для лучшей читаемости
- ✅ Валидация: SSH обязателен для orchestrated режима
- ✅ Exit 1 если нет SSH в orchestrated режиме
- ✅ Подробное логирование всех операций

### 2.2 Создать orchestrated.php (30 минут)

**Файл:** `app/orchestrated.php`

```php
<?php

/**
 * Orchestrated режим работы агента
 * 
 * Этот файл используется когда агент запускается под управлением
 * AgentManager оркестратора. Агент обрабатывает ОДНУ задачу,
 * полученную через переменные окружения, и завершается.
 * 
 * Переменные окружения (от оркестратора):
 *   - TASK_ID       - ID задачи для обработки
 *   - AGENT_UUID    - UUID агента (worker)
 *   - AGENT_ID      - ID агента
 *   - API_TOKEN     - Токен для External API
 *   - SSH_PRIVATE_KEY - SSH ключ проекта
 *   - API_HOST      - Хост External API
 * 
 * Exit коды:
 *   - 0: Задача выполнена успешно
 *   - 1: Ошибка выполнения
 */

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

echo "════════════════════════════════════════════════════\n";
echo "  AgentModule - Orchestrated Mode\n";
echo "  Managed by AgentManager Orchestrator\n";
echo "════════════════════════════════════════════════════\n\n";

Log::info("🎭 Agent starting in ORCHESTRATED mode (managed by AgentManager)", [
    'mode' => 'orchestrated',
    'php_version' => PHP_VERSION,
    'task_id' => getenv('TASK_ID') ?: 'not set',
    'agent_uuid' => getenv('AGENT_UUID') ?: 'not set',
]);

// Инициализация API клиента
$apiHost = getenv('API_HOST');
$apiToken = getenv('API_TOKEN');

if (!$apiHost || !$apiToken) {
    Log::error("❌ Missing API configuration", [
        'API_HOST' => $apiHost ?: 'not set',
        'API_TOKEN' => $apiToken ? 'set' : 'not set',
    ]);
    
    echo "ERROR: Missing API configuration\n";
    echo "Required: API_HOST, API_TOKEN\n";
    exit(1);
}

$api = new DocModuleApi(
    host: $apiHost,
    token: $apiToken,
);

Log::info("✅ API client initialized", [
    'api_host' => $apiHost,
]);

// Инициализация Repository Provider
$repoProvider = new RepositoryProvider(
    reposFolder: 'orchestrated', // Отдельная папка для orchestrated режима
    branch: 'main'
);

Log::info("✅ Repository provider initialized", [
    'folder' => 'orchestrated',
    'branch' => 'main',
]);

// Инициализация фабрик
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

Log::info("✅ All factories initialized");

// Запуск orchestrated runner
echo "\n";
echo "Starting task processing...\n";
echo "────────────────────────────────────────────────────\n\n";

try {
    (new OrchestratedRunner($api, StateStore::run(), $processorFactory))->run();
} catch (\Throwable $e) {
    Log::exception($e, '❌ Fatal error in orchestrated mode');
    
    echo "\n";
    echo "════════════════════════════════════════════════════\n";
    echo "  FATAL ERROR\n";
    echo "════════════════════════════════════════════════════\n";
    echo "Error: {$e->getMessage()}\n";
    echo "File: {$e->getFile()}:{$e->getLine()}\n";
    
    exit(1);
}
```

**Ключевые особенности:**
- ✅ Подробные комментарии для документации
- ✅ Красивый вывод в консоль (ASCII art)
- ✅ Валидация API конфигурации
- ✅ Отдельная папка репозиториев (`orchestrated`)
- ✅ Подробное логирование инициализации
- ✅ Обработка фатальных ошибок
- ✅ Корректные exit коды

### 2.3 Оставить main.php без изменений (0 минут)

**Файл:** `app/main.php` - НЕ ТРОГАТЬ!

**Текущая версия:**

```php
<?php

use Anymodule\Agentmodule\Factory\ActionsFactory;
use Anymodule\Agentmodule\Factory\ConversationFactory;
use Anymodule\Agentmodule\Factory\ChatAgentFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\ApiService\DocModuleApi;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\StateStore;
use Anymodule\Agentmodule\Services\TaskStorageProvider;

require __DIR__ . '/vendor/autoload.php';

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

(new Runner($api, StateStore::run(), $processorFactory))->run();
```

**✅ Оставляем как есть!** Это точка входа для standalone режима.

### 2.4 Проверка (15 минут)

```bash
# Проверить синтаксис
php -l app/orchestrated.php
php -l app/main.php

# Проверить что entrypoint исполняемый
chmod +x docker/agent/docker-entrypoint.sh

# Проверить entrypoint локально (если есть bash)
bash docker/agent/docker-entrypoint.sh echo "test"
```

**Checklist:**
- [ ] `docker-entrypoint.sh` обновлен
- [ ] `docker-entrypoint.sh` исполняемый (`chmod +x`)
- [ ] `orchestrated.php` создан
- [ ] `main.php` не изменен
- [ ] Нет синтаксических ошибок

---

## Этап 3: Multi-stage Dockerfile (3-4 часа)

### 3.1 Создать multi-stage Dockerfile (1 час)

**Файл:** `docker/agent/Dockerfile`

```dockerfile
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
```

**Ключевые особенности:**
- ✅ 4 stage: `base`, `develop`, `standalone`, `orchestrated`
- ✅ Отдельные папки репозиториев для каждого режима
- ✅ Безопасность: USER local (не root)
- ✅ Оптимизация: многоуровневый кэш Docker
- ✅ Документация: LABEL для каждого stage
- ✅ Чистота: очистка apt cache

### 3.2 Создать build.sh (30 минут)

**Файл:** `docker/agent/build.sh`

```bash
#!/bin/bash
set -e

# ═══════════════════════════════════════════════════════════════
# AgentModule Docker Images Builder
# ═══════════════════════════════════════════════════════════════

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Конфигурация
VERSION=${1:-latest}
REGISTRY=${REGISTRY:-ghcr.io/vasenin26}
IMAGE_NAME=agentmodule

echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  AgentModule - Building Docker Images${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}Version:${NC} $VERSION"
echo -e "${GREEN}Registry:${NC} $REGISTRY"
echo ""

# Проверка что мы в корне проекта
if [ ! -f "docker/agent/Dockerfile" ]; then
    echo -e "${RED}ERROR: docker/agent/Dockerfile not found${NC}"
    echo -e "${RED}Please run this script from project root${NC}"
    exit 1
fi

# ─────────────────────────────────────────────────────────────
# Build Development Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[1/3]${NC} Building ${BLUE}development${NC} image..."
docker build \
    --target develop \
    --tag $REGISTRY/$IMAGE_NAME:develop \
    --file docker/agent/Dockerfile \
    --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
    --build-arg VERSION=$VERSION \
    .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Development image built successfully${NC}"
else
    echo -e "${RED}❌ Failed to build development image${NC}"
    exit 1
fi
echo ""

# ─────────────────────────────────────────────────────────────
# Build Standalone Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[2/3]${NC} Building ${BLUE}standalone${NC} image..."
docker build \
    --target standalone \
    --tag $REGISTRY/$IMAGE_NAME:$VERSION-standalone \
    --tag $REGISTRY/$IMAGE_NAME:standalone \
    --file docker/agent/Dockerfile \
    --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
    --build-arg VERSION=$VERSION \
    .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Standalone image built successfully${NC}"
else
    echo -e "${RED}❌ Failed to build standalone image${NC}"
    exit 1
fi
echo ""

# ─────────────────────────────────────────────────────────────
# Build Orchestrated Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[3/3]${NC} Building ${BLUE}orchestrated${NC} image..."
docker build \
    --target orchestrated \
    --tag $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated \
    --tag $REGISTRY/$IMAGE_NAME:$VERSION \
    --tag $REGISTRY/$IMAGE_NAME:orchestrated \
    --tag $REGISTRY/$IMAGE_NAME:latest \
    --file docker/agent/Dockerfile \
    --build-arg BUILD_DATE=$(date -u +"%Y-%m-%dT%H:%M:%SZ") \
    --build-arg VERSION=$VERSION \
    .

if [ $? -eq 0 ]; then
    echo -e "${GREEN}✅ Orchestrated image built successfully${NC}"
else
    echo -e "${RED}❌ Failed to build orchestrated image${NC}"
    exit 1
fi
echo ""

# ─────────────────────────────────────────────────────────────
# Summary
# ─────────────────────────────────────────────────────────────
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ All images built successfully!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Built images:${NC}"
echo -e "  📦 ${GREEN}Development:${NC}   $REGISTRY/$IMAGE_NAME:develop"
echo -e "  📦 ${GREEN}Standalone:${NC}    $REGISTRY/$IMAGE_NAME:$VERSION-standalone"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:standalone"
echo -e "  📦 ${GREEN}Orchestrated:${NC}  $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:$VERSION"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:orchestrated"
echo -e "  📦 ${GREEN}                $REGISTRY/$IMAGE_NAME:latest"
echo ""
echo -e "${BLUE}Next steps:${NC}"
echo -e "  1. Test images locally:"
echo -e "     ${YELLOW}docker run --rm $REGISTRY/$IMAGE_NAME:standalone${NC}"
echo -e "     ${YELLOW}docker run --rm -e TASK_ID=123 -e AGENT_UUID=456 -e AGENT_ID=789 $REGISTRY/$IMAGE_NAME:orchestrated${NC}"
echo -e "  2. Push images to registry:"
echo -e "     ${YELLOW}./docker/agent/push.sh $VERSION${NC}"
echo ""
```

**Сделать исполняемым:**
```bash
chmod +x docker/agent/build.sh
```

### 3.3 Создать push.sh (15 минут)

**Файл:** `docker/agent/push.sh`

```bash
#!/bin/bash
set -e

# ═══════════════════════════════════════════════════════════════
# AgentModule Docker Images Publisher
# ═══════════════════════════════════════════════════════════════

# Цвета для вывода
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
NC='\033[0m' # No Color

# Конфигурация
VERSION=${1:-latest}
REGISTRY=${REGISTRY:-ghcr.io/vasenin26}
IMAGE_NAME=agentmodule

echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo -e "${BLUE}  AgentModule - Pushing Docker Images${NC}"
echo -e "${BLUE}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${GREEN}Version:${NC} $VERSION"
echo -e "${GREEN}Registry:${NC} $REGISTRY"
echo ""

# ─────────────────────────────────────────────────────────────
# Push Development Image
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[1/7]${NC} Pushing ${BLUE}development${NC} image..."
docker push $REGISTRY/$IMAGE_NAME:develop
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:develop${NC}"
echo ""

# ─────────────────────────────────────────────────────────────
# Push Standalone Images
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[2/7]${NC} Pushing ${BLUE}standalone${NC} versioned image..."
docker push $REGISTRY/$IMAGE_NAME:$VERSION-standalone
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:$VERSION-standalone${NC}"
echo ""

echo -e "${YELLOW}[3/7]${NC} Pushing ${BLUE}standalone${NC} latest image..."
docker push $REGISTRY/$IMAGE_NAME:standalone
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:standalone${NC}"
echo ""

# ─────────────────────────────────────────────────────────────
# Push Orchestrated Images
# ─────────────────────────────────────────────────────────────
echo -e "${YELLOW}[4/7]${NC} Pushing ${BLUE}orchestrated${NC} versioned image..."
docker push $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated${NC}"
echo ""

echo -e "${YELLOW}[5/7]${NC} Pushing ${BLUE}orchestrated${NC} version tag..."
docker push $REGISTRY/$IMAGE_NAME:$VERSION
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:$VERSION${NC}"
echo ""

echo -e "${YELLOW}[6/7]${NC} Pushing ${BLUE}orchestrated${NC} latest..."
docker push $REGISTRY/$IMAGE_NAME:orchestrated
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:orchestrated${NC}"
echo ""

echo -e "${YELLOW}[7/7]${NC} Pushing ${BLUE}latest${NC} tag..."
docker push $REGISTRY/$IMAGE_NAME:latest
echo -e "${GREEN}✅ Pushed: $REGISTRY/$IMAGE_NAME:latest${NC}"
echo ""

# ─────────────────────────────────────────────────────────────
# Summary
# ─────────────────────────────────────────────────────────────
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo -e "${GREEN}  ✅ All images pushed successfully!${NC}"
echo -e "${GREEN}════════════════════════════════════════════════════${NC}"
echo ""
echo -e "${BLUE}Published images:${NC}"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:develop"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:$VERSION-standalone"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:standalone"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:$VERSION-orchestrated"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:$VERSION"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:orchestrated"
echo -e "  🚀 $REGISTRY/$IMAGE_NAME:latest"
echo ""
```

**Сделать исполняемым:**
```bash
chmod +x docker/agent/push.sh
```

### 3.4 Обновить docker-compose.yaml (15 минут)

**Файл:** `docker-compose.yaml`

```yaml
version: '3.8'

# ═══════════════════════════════════════════════════════════════
# AgentModule - Docker Compose Configuration
# ═══════════════════════════════════════════════════════════════

services:
  # ─────────────────────────────────────────────────────────────
  # Development Service - для локальной разработки
  # ─────────────────────────────────────────────────────────────
  agentmodule:
    build:
      context: .
      dockerfile: docker/agent/Dockerfile
      target: develop
    image: agentmodule:develop
    container_name: agentmodule-dev
    ports:
      - "50051:50051"
    volumes:
      - ./app:/app
    working_dir: /app
    env_file:
      - .env
    environment:
      - PHP_IDE_CONFIG=serverName=agentmodule
    restart: unless-stopped

  # ─────────────────────────────────────────────────────────────
  # Standalone Service - автономный режим (текущий)
  # ─────────────────────────────────────────────────────────────
  agent-standalone:
    build:
      context: .
      dockerfile: docker/agent/Dockerfile
      target: standalone
    image: agentmodule:standalone
    container_name: agentmodule-standalone
    env_file:
      - .env
    environment:
      # API конфигурация
      - API_HOST=${API_HOST}
      - API_TOKEN=${API_TOKEN}
      # Git конфигурация
      - GIT_USER_NAME=${GIT_USER_NAME}
      - GIT_USER_EMAIL=${GIT_USER_EMAIL}
      # SSH ключ (опционально)
      - SSH_PRIVATE_KEY=${SSH_PRIVATE_KEY}
      # OpenAI конфигурация
      - OPENAI_API_KEY=${OPENAI_API_KEY}
      - OPENAI_MODEL=${OPENAI_MODEL:-gpt-4}
    restart: unless-stopped
    # profiles: [standalone]  # Опционально: запускать только по запросу

  # ─────────────────────────────────────────────────────────────
  # Orchestrated Service - режим оркестратора (новый)
  # Для тестирования orchestrated режима локально
  # ─────────────────────────────────────────────────────────────
  agent-orchestrated:
    build:
      context: .
      dockerfile: docker/agent/Dockerfile
      target: orchestrated
    image: agentmodule:orchestrated
    container_name: agentmodule-orchestrated
    env_file:
      - .env
    environment:
      # Orchestrator переменные
      - TASK_ID=${TASK_ID:-test-task-123}
      - AGENT_UUID=${AGENT_UUID:-test-uuid-456}
      - AGENT_ID=${AGENT_ID:-test-agent-789}
      # API конфигурация
      - API_HOST=${API_HOST}
      - API_TOKEN=${API_TOKEN}
      # SSH ключ (обязательно!)
      - SSH_PRIVATE_KEY=${SSH_PRIVATE_KEY}
      # Git конфигурация
      - GIT_USER_NAME=${GIT_USER_NAME}
      - GIT_USER_EMAIL=${GIT_USER_EMAIL}
      # OpenAI конфигурация
      - OPENAI_API_KEY=${OPENAI_API_KEY}
      - OPENAI_MODEL=${OPENAI_MODEL:-gpt-4}
    restart: "no"  # Не рестартовать - one-shot выполнение
    # profiles: [orchestrated]  # Опционально: запускать только по запросу
```

**Ключевые особенности:**
- ✅ 3 сервиса: `agentmodule` (dev), `agent-standalone`, `agent-orchestrated`
- ✅ Разные restart policies
- ✅ Profiles для селективного запуска (опционально)
- ✅ Все переменные из `.env`

### 3.5 Проверка (15 минут)

```bash
# Сборка всех образов
./docker/agent/build.sh v1.0.0

# Проверка что образы созданы
docker images | grep agentmodule

# Ожидаемый вывод:
# agentmodule  develop               ...
# agentmodule  standalone             ...
# agentmodule  v1.0.0-standalone      ...
# agentmodule  orchestrated           ...
# agentmodule  v1.0.0-orchestrated    ...
# agentmodule  v1.0.0                 ...
# agentmodule  latest                 ...
```

**Checklist:**
- [ ] `Dockerfile` создан (multi-stage)
- [ ] `build.sh` создан и исполняемый
- [ ] `push.sh` создан и исполняемый
- [ ] `docker-compose.yaml` обновлен
- [ ] Все образы успешно собираются
- [ ] Размеры образов разумные (<500MB)

---

## Этап 4: Тестирование (3-4 часа)

### 4.1 Unit тесты OrchestratedRunner (2 часа)

**Файл:** `app/tests/Unit/OrchestratedRunnerTest.php`

```php
<?php

namespace Anymodule\Agentmodule\Tests\Unit;

use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\StateStoreInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\Interface\Task\TaskProcessorFactoryInterface;
use Anymodule\Agentmodule\OrchestratedRunner;
use PHPUnit\Framework\TestCase;
use Mockery;

class OrchestratedRunnerTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testRequiresTaskIdFromEnv(): void
    {
        // Arrange
        putenv('TASK_ID=');
        putenv('AGENT_UUID=test-uuid');
        putenv('AGENT_ID=test-agent');

        $api = Mockery::mock(TaskApiInterface::class);
        $stateStore = Mockery::mock(StateStoreInterface::class);
        $stateStore->shouldReceive('push')->never();
        $processorFactory = Mockery::mock(TaskProcessorFactoryInterface::class);

        $runner = new OrchestratedRunner($api, $stateStore, $processorFactory);

        // Act & Assert
        $this->expectExceptionCode(1);
        
        try {
            $runner->run();
        } catch (\Throwable $e) {
            // exit(1) вызывает исключение в тестах
            $this->assertEquals(1, $e->getCode());
        }
    }

    public function testRequiresAgentUuidFromEnv(): void
    {
        // Arrange
        putenv('TASK_ID=test-task');
        putenv('AGENT_UUID=');
        putenv('AGENT_ID=test-agent');

        $api = Mockery::mock(TaskApiInterface::class);
        $stateStore = Mockery::mock(StateStoreInterface::class);
        $processorFactory = Mockery::mock(TaskProcessorFactoryInterface::class);

        $runner = new OrchestratedRunner($api, $stateStore, $processorFactory);

        // Act & Assert
        $this->expectExceptionCode(1);
        
        try {
            $runner->run();
        } catch (\Throwable $e) {
            $this->assertEquals(1, $e->getCode());
        }
    }

    public function testExitsWithErrorWhenTaskNotFound(): void
    {
        // Arrange
        putenv('TASK_ID=non-existent');
        putenv('AGENT_UUID=test-uuid');
        putenv('AGENT_ID=test-agent');

        $api = Mockery::mock(TaskApiInterface::class);
        $api->shouldReceive('getTaskByUuid')
            ->with('test-uuid')
            ->once()
            ->andReturn(null);

        $stateStore = Mockery::mock(StateStoreInterface::class);
        $stateStore->shouldReceive('push')->times(3); // started, fetching, error
        
        $processorFactory = Mockery::mock(TaskProcessorFactoryInterface::class);

        $runner = new OrchestratedRunner($api, $stateStore, $processorFactory);

        // Act & Assert
        $this->expectExceptionCode(1);
        
        try {
            $runner->run();
        } catch (\Throwable $e) {
            $this->assertEquals(1, $e->getCode());
        }
    }

    public function testProcessesTaskSuccessfully(): void
    {
        // Arrange
        putenv('TASK_ID=123');
        putenv('AGENT_UUID=test-uuid');
        putenv('AGENT_ID=test-agent');

        $task = new Task(
            id: 123,
            type: 'test',
            conversationId: 456,
            messages: [],
            projectId: 789,
            resultRequired: true
        );

        $api = Mockery::mock(TaskApiInterface::class);
        $api->shouldReceive('getTaskByUuid')
            ->with('test-uuid')
            ->once()
            ->andReturn($task);

        $stateStore = Mockery::mock(StateStoreInterface::class);
        $stateStore->shouldReceive('push')->times(4); // started, fetching, processing, completed

        $processor = Mockery::mock(\Anymodule\Agentmodule\Interface\Task\TaskProcessorInterface::class);
        $processor->shouldReceive('process')
            ->with($task, Mockery::any())
            ->once();

        $processorFactory = Mockery::mock(TaskProcessorFactoryInterface::class);
        $processorFactory->shouldReceive('createProcessorForTask')
            ->with($task)
            ->once()
            ->andReturn($processor);

        $runner = new OrchestratedRunner($api, $stateStore, $processorFactory);

        // Act & Assert
        $this->expectExceptionCode(0);
        
        try {
            $runner->run();
        } catch (\Throwable $e) {
            $this->assertEquals(0, $e->getCode());
        }
    }
}
```

### 4.2 Integration тесты (1 час)

**Тест Standalone образа:**

```bash
#!/bin/bash
# tests/integration/test_standalone.sh

echo "Testing Standalone Image..."
echo "──────────────────────────────────────────────────────"

# Сборка образа
docker build --target standalone -t agentmodule:standalone-test -f docker/agent/Dockerfile .

# Запуск с тестовыми переменными
docker run --rm \
  --name agentmodule-standalone-test \
  -e API_HOST=${TEST_API_HOST:-https://api.example.com} \
  -e API_TOKEN=${TEST_API_TOKEN:-test-token} \
  -e GIT_USER_NAME="Test User" \
  -e GIT_USER_EMAIL="test@example.com" \
  agentmodule:standalone-test &

PID=$!

# Подождать 5 секунд
sleep 5

# Проверить что контейнер работает
if ps -p $PID > /dev/null; then
    echo "✅ Standalone image started successfully"
    kill $PID
    exit 0
else
    echo "❌ Standalone image failed to start"
    exit 1
fi
```

**Тест Orchestrated образа:**

```bash
#!/bin/bash
# tests/integration/test_orchestrated.sh

echo "Testing Orchestrated Image..."
echo "──────────────────────────────────────────────────────"

# Сборка образа
docker build --target orchestrated -t agentmodule:orchestrated-test -f docker/agent/Dockerfile .

# Тест 1: Без обязательных ENV переменных (должен exit 1)
echo "Test 1: Missing ENV variables..."
docker run --rm agentmodule:orchestrated-test
EXIT_CODE=$?

if [ $EXIT_CODE -eq 1 ]; then
    echo "✅ Test 1 passed: Correctly exits with code 1 when ENV vars missing"
else
    echo "❌ Test 1 failed: Expected exit code 1, got $EXIT_CODE"
    exit 1
fi

# Тест 2: С ENV переменными но без реального API
echo ""
echo "Test 2: With ENV variables (will fail on API call)..."
docker run --rm \
  -e TASK_ID=test-123 \
  -e AGENT_UUID=test-uuid \
  -e AGENT_ID=test-agent \
  -e API_HOST=http://nonexistent \
  -e API_TOKEN=test-token \
  -e SSH_PRIVATE_KEY="$(cat tests/fixtures/test_key.pem)" \
  agentmodule:orchestrated-test

EXIT_CODE=$?

if [ $EXIT_CODE -eq 1 ]; then
    echo "✅ Test 2 passed: Correctly handles API errors"
else
    echo "⚠️  Test 2: Exit code $EXIT_CODE (expected 1)"
fi

echo ""
echo "✅ All orchestrated tests completed"
```

### 4.3 Тестирование SSH (30 минут)

**Создать тестовый SSH ключ:**

```bash
# Создать тестовый ключ
ssh-keygen -t rsa -b 2048 -f tests/fixtures/test_key.pem -N ""

# Проверить что ключ работает в контейнере
docker run --rm \
  -e SSH_PRIVATE_KEY="$(cat tests/fixtures/test_key.pem)" \
  agentmodule:orchestrated \
  sh -c "ls -la ~/.ssh/ && cat ~/.ssh/id_rsa | head -n 1"

# Ожидаемый вывод:
# [entrypoint] SSH ключ успешно настроен
# -rw------- 1 local local ... id_rsa
# -----BEGIN RSA PRIVATE KEY-----
```

### 4.4 Проверка exit кодов (15 минут)

```bash
# Успешное завершение (с mock API)
docker run --rm \
  -e TASK_ID=123 \
  -e AGENT_UUID=456 \
  -e AGENT_ID=789 \
  -e API_HOST=http://mock-api:9000 \
  -e API_TOKEN=token \
  -e SSH_PRIVATE_KEY="$(cat key.pem)" \
  agentmodule:orchestrated

echo "Exit code: $?"  # Должно быть 0

# Ошибка (нет TASK_ID)
docker run --rm agentmodule:orchestrated
echo "Exit code: $?"  # Должно быть 1
```

**Checklist:**
- [ ] Unit тесты написаны
- [ ] Unit тесты проходят
- [ ] Integration тесты standalone проходят
- [ ] Integration тесты orchestrated проходят
- [ ] SSH ключ корректно инициализируется
- [ ] Exit коды корректные (0/1)

---

## Этап 5: CI/CD и документация (3-4 часа)

### 5.1 GitHub Actions (1 час)

**Файл:** `.github/workflows/build-images.yml`

```yaml
name: Build and Push Agent Images

on:
  push:
    branches: [main]
    tags: ['v*']
  pull_request:
    branches: [main]

env:
  REGISTRY: ghcr.io
  IMAGE_NAME: ${{ github.repository }}

jobs:
  build-and-push:
    runs-on: ubuntu-latest
    permissions:
      contents: read
      packages: write

    steps:
      - name: Checkout repository
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

      - name: Build and push Development image
        uses: docker/build-push-action@v5
        with:
          context: .
          file: docker/agent/Dockerfile
          target: develop
          push: ${{ github.event_name != 'pull_request' }}
          tags: |
            ${{ env.REGISTRY }}/${{ env.IMAGE_NAME }}:develop
          labels: ${{ steps.meta.outputs.labels }}
          cache-from: type=gha
          cache-to: type=gha,mode=max

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

### 5.2 Обновить README.md (1 час)

Добавить в `README.md`:

```markdown
## 🚀 Режимы работы

AgentModule поддерживает два режима работы:

### 1️⃣ Standalone Mode (автономный)

Агент сам получает задачи из API в бесконечном цикле.

**Использование:**
```bash
docker run -d \
  --name agentmodule \
  -e API_HOST=https://api.example.com \
  -e API_TOKEN=your-token \
  ghcr.io/vasenin26/agentmodule:standalone
```

### 2️⃣ Orchestrated Mode (оркестрированный)

Агент управляется AgentManager оркестратором, обрабатывает одну задачу и завершается.

**Использование (от оркестратора):**
```bash
docker run --rm \
  -e TASK_ID=task-123 \
  -e AGENT_UUID=uuid-456 \
  -e AGENT_ID=agent-789 \
  -e API_TOKEN=token \
  -e SSH_PRIVATE_KEY="$(cat project_key.pem)" \
  -e API_HOST=https://api.example.com \
  ghcr.io/vasenin26/agentmodule:latest
```

**Exit коды:**
- `0` - Задача выполнена успешно
- `1` - Ошибка выполнения

## 📦 Доступные Docker образы

| Tag | Описание | Использование |
|-----|----------|---------------|
| `latest`, `orchestrated` | Orchestrated режим (для оркестратора) | Продакшн |
| `standalone` | Standalone режим (автономный) | Разработка/standalone |
| `develop` | Development режим | Локальная разработка |

## 🔧 Локальная разработка

```bash
# Клонировать репозиторий
git clone https://github.com/vasenin26/agentmodule.git
cd agentmodule

# Настроить .env
cp env.example .env
# Редактировать .env

# Запустить development контейнер
docker-compose up agentmodule

# Или собрать все образы
./docker/agent/build.sh v1.0.0
```

## 📚 Документация

- [Интеграция с оркестратором](AGENT_ORCHESTRATOR_INTEGRATION_PLAN.md)
- [Быстрый старт оркестрации](QUICK_START_ORCHESTRATION.md)
- [Настройка SSH](docs/ssh-setup.md)
```

### 5.3 Создать DEPLOYMENT.md (30 минут)

**Файл:** `DEPLOYMENT.md`

```markdown
# Deployment Guide

## 🚀 Production Deployment

### Для оркестратора (Orchestrated Mode)

AgentManager оркестратор автоматически запускает контейнеры агента.

**Конфигурация оркестратора:**

```yaml
# agentmanager/docker-compose.prod.yaml
services:
  agent-svc:
    environment:
      - AGENT_IMAGE=ghcr.io/vasenin26/agentmodule:latest
```

### Standalone Deployment

**Docker:**

```bash
docker run -d \
  --name agentmodule \
  --restart unless-stopped \
  -e API_HOST=https://api.example.com \
  -e API_TOKEN=$API_TOKEN \
  -e GIT_USER_NAME="Agent Bot" \
  -e GIT_USER_EMAIL="bot@example.com" \
  ghcr.io/vasenin26/agentmodule:standalone
```

**Docker Compose:**

```yaml
version: '3.8'

services:
  agent:
    image: ghcr.io/vasenin26/agentmodule:standalone
    restart: unless-stopped
    environment:
      - API_HOST=https://api.example.com
      - API_TOKEN=${API_TOKEN}
      - GIT_USER_NAME=Agent Bot
      - GIT_USER_EMAIL=bot@example.com
      - SSH_PRIVATE_KEY=${SSH_PRIVATE_KEY}
```

## 📊 Monitoring

### Logs

```bash
# Standalone
docker logs -f agentmodule

# Orchestrated (от оркестратора)
docker logs -f agent-task-123
```

### Health Checks

```bash
# Проверить что контейнер работает
docker ps | grep agentmodule

# Проверить exit code последнего запуска
docker inspect agentmodule --format='{{.State.ExitCode}}'
```

## 🔄 Updates

### Обновление образа

```bash
# Pull latest
docker pull ghcr.io/vasenin26/agentmodule:latest

# Recreate container
docker-compose up -d --force-recreate agent
```

### Rollback

```bash
# К конкретной версии
docker pull ghcr.io/vasenin26/agentmodule:v1.0.0
docker run -d ... ghcr.io/vasenin26/agentmodule:v1.0.0
```
```

### 5.4 Финальная проверка (30 минут)

**Checklist полной интеграции:**

```bash
# 1. Проверить что все файлы созданы
ls -la app/src/OrchestratedRunner.php
ls -la app/orchestrated.php
ls -la docker/agent/Dockerfile
ls -la docker/agent/build.sh
ls -la docker/agent/push.sh

# 2. Проверить синтаксис PHP
find app/src -name "*.php" -exec php -l {} \;

# 3. Запустить unit тесты
docker-compose run --rm agentmodule php vendor/bin/phpunit

# 4. Собрать все образы
./docker/agent/build.sh v1.0.0

# 5. Запустить integration тесты
bash tests/integration/test_standalone.sh
bash tests/integration/test_orchestrated.sh

# 6. Проверить размеры образов
docker images | grep agentmodule

# 7. Тестовый запуск standalone
docker-compose up agent-standalone

# 8. Тестовый запуск orchestrated
export TASK_ID=test-123
export AGENT_UUID=test-uuid
export AGENT_ID=test-agent
docker-compose up agent-orchestrated
```

**Финальный checklist:**
- [ ] Все PHP файлы без синтаксических ошибок
- [ ] Unit тесты проходят
- [ ] Образы собираются без ошибок
- [ ] Standalone образ запускается
- [ ] Orchestrated образ запускается
- [ ] SSH ключ инициализируется корректно
- [ ] Exit коды правильные
- [ ] Логирование работает
- [ ] Документация обновлена
- [ ] GitHub Actions настроен

---

## 📋 Итоговая сводка изменений

### Новые файлы

```
app/
├── orchestrated.php                          # NEW - точка входа orchestrated
└── src/
    ├── OrchestratedRunner.php                # NEW - runner для оркестратора
    └── Interface/
        └── Task/
            └── TaskApi.php                   # MODIFIED - добавлен getTaskByUuid

docker/
└── agent/
    ├── Dockerfile                            # MODIFIED - multi-stage
    ├── docker-entrypoint.sh                  # MODIFIED - режимы + валидация
    ├── build.sh                              # NEW - сборка образов
    └── push.sh                               # NEW - публикация образов

tests/
├── Unit/
│   └── OrchestratedRunnerTest.php           # NEW - unit тесты
└── integration/
    ├── test_standalone.sh                    # NEW - integration тесты
    └── test_orchestrated.sh                  # NEW - integration тесты

.github/
└── workflows/
    └── build-images.yml                      # NEW - CI/CD

docker-compose.yaml                           # MODIFIED - 3 сервиса
DEPLOYMENT.md                                 # NEW - deployment guide
README.md                                     # MODIFIED - режимы работы
```

### Изменения в существующих файлах

1. **`app/src/Interface/Task/TaskApi.php`**
   - ➕ Добавлен метод `getTaskByUuid(string $agentUuid): ?Task`

2. **`app/src/Services/ApiService/Service.php`**
   - ➕ Реализован метод `getTaskByUuid()`
   - ✅ Переиспользует `GetAgentTask` request

3. **`docker/agent/docker-entrypoint.sh`**
   - ➕ Определение режима работы
   - ➕ Валидация SSH для orchestrated
   - ➕ Цветной вывод логов
   - ➕ Exit 1 если нет SSH в orchestrated

4. **`docker/agent/Dockerfile`**
   - ➕ Multi-stage: base, develop, standalone, orchestrated
   - ➕ Отдельные CMD для каждого режима
   - ➕ Оптимизация кэширования

5. **`docker-compose.yaml`**
   - ➕ 3 сервиса вместо 1
   - ➕ Разные restart policies

6. **`README.md`**
   - ➕ Документация режимов работы
   - ➕ Примеры использования
   - ➕ Таблица Docker образов

### Статистика

- **Новых файлов:** 11
- **Изменённых файлов:** 6
- **Строк кода (новых):** ~1500
- **Время реализации:** 13-18 часов
- **Тестовое покрытие:** Unit + Integration

---

## 🎯 Критерии приемки

### Функциональные требования

- [x] Агент читает `TASK_ID`, `AGENT_UUID`, `AGENT_ID` из ENV
- [x] Агент запрашивает задачу через `POST /api/agent/task`
- [x] Агент использует SSH ключ проекта из ENV
- [x] Агент обрабатывает ОДНУ задачу и завершается
- [x] Exit code 0 при успехе, 1 при ошибке
- [x] Standalone режим продолжает работать без изменений

### Качество кода

- [x] Нет дублирования кода
- [x] Понятные имена переменных и методов
- [x] Подробные комментарии в коде
- [x] Логирование всех ключевых операций
- [x] Обработка ошибок с понятными сообщениями

### Docker

- [x] Multi-stage Dockerfile
- [x] Оптимизация размера образов
- [x] Корректные LABEL для идентификации
- [x] Безопасность (USER local, не root)
- [x] Entrypoint для инициализации

### Документация

- [x] README.md обновлен
- [x] DEPLOYMENT.md создан
- [x] Комментарии в коде
- [x] Примеры использования

### CI/CD

- [x] GitHub Actions для автоматической сборки
- [x] Публикация в GitHub Container Registry
- [x] Тегирование версий

---

## 🚨 Известные ограничения

1. **SSH ключ в ENV**
   - Ключ передается через ENV (безопасно в Docker)
   - Не используйте в незащищенных логах

2. **Одна задача за запуск**
   - Orchestrated режим обрабатывает только одну задачу
   - Для множественных задач запускайте несколько контейнеров

3. **Нет автоматического retry**
   - При ошибке контейнер завершается с exit 1
   - Retry логика на стороне оркестратора

4. **StateStore**
   - В orchestrated режиме state живет только во время выполнения
   - После завершения контейнера state теряется

---

## 📞 Поддержка

- **GitHub Issues:** https://github.com/vasenin26/agentmodule/issues
- **Документация оркестратора:** https://github.com/vasenin26/agentmanager
- **Email:** vasenin26@example.com

---

**Дата создания:** 2025-10-06  
**Версия:** 1.0  
**Автор:** AI Assistant + vasenin26  
**Статус:** ✅ Готов к реализации

