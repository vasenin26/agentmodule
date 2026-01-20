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

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Anymodule\Agentmodule\Interface\Task\TaskApiInterface;
use Anymodule\Agentmodule\OrchestratedRunner;
use Anymodule\Agentmodule\Policy\TaskProcessing\TaskProcessorRouter;
use Anymodule\Agentmodule\Services\StateStore;

require __DIR__ . '/vendor/autoload.php';

/**
 * @var $container DI\Container
 */
$container = require __DIR__ . '/bootstrap/container.php';

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

Log::info("✅ API client initialized", [
    'api_host' => $apiHost,
]);

Log::info("✅ Repository provider initialized", [
    'folder' => 'orchestrated',
    'branch' => 'main',
]);

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
    $agentMeta = $container->get(AgentMetaProviderInterface::class);

    (new OrchestratedRunner(
        $container->get(TaskApiInterface::class),
        StateStore::run(),
        $container->get(TaskProcessorRouter::class),
    )
    )->run($taskId, $agentMeta->getAgentUuid());
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

