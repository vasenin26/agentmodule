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
use Anymodule\Agentmodule\Factory\LLMFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\OrchestratedRunner;
use Anymodule\Agentmodule\Services\ApiService\Service;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\StateStore;
use Anymodule\Agentmodule\Services\TaskStorageProvider;
use Anymodule\Agentmodule\Utils\Log;
use Ramsey\Uuid\Uuid;

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

$api = new Service(
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

$llmFactory = new LLMFactory($repoProvider);

$processorFactory = new TaskProcessorFactory(
    $toolFactory,
    $llmFactory,
    new ConversationFactory(),
    new TaskStorageProvider(),
    new ActionsFactory($toolFactory, $llmFactory),
);

Log::info("✅ All factories initialized");

// Получить переменные окружения от оркестратора
$taskId = getenv('TASK_ID');
$agentUuid = getenv('AGENT_UUID');

// Валидация обязательных переменных
if (!$taskId || !$agentUuid) {
    Log::error('Missing required environment variables for orchestrated mode', [
        'TASK_ID' => $taskId ?: 'not set',
        'AGENT_UUID' => $agentUuid ?: 'not set',
    ]);

    echo "ERROR: Missing required environment variables\n";
    echo "Required: TASK_ID, AGENT_UUID, AGENT_ID\n";

    exit(1);
}

// Запуск orchestrated runner
echo "\n";
echo "Starting task processing...\n";
echo "────────────────────────────────────────────────────\n\n";

try {
    (new OrchestratedRunner($api, StateStore::run(), $processorFactory))->run($taskId, Uuid::fromString($agentUuid));
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

