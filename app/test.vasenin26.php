<?php

use Anymodule\Agentmodule\Entity\Conversation\Chat;
use Anymodule\Agentmodule\Entity\Conversation\Messages\UserMessage;
use Anymodule\Agentmodule\Factory\LLMFactory;
use Anymodule\Agentmodule\Factory\PageContextProviderFactory;
use Anymodule\Agentmodule\Factory\TaskProcessorFactory;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Runner;
use Anymodule\Agentmodule\Services\ApiService\Service;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\LLMGenerator\LMStudioClient;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Anymodule\Agentmodule\Services\ToolsService\ToolsFactory;

require __DIR__ . '/vendor/autoload.php';

$api = new Service(
    host: getenv('API_HOST'),
    token: getenv('AGENT_TOKEN'),
);

// Создаем репозиторий провайдер
$repositoryProvider = new RepositoryProvider($api);

// Создаем фабрику для страниц
$pageContextProviderFactory = new PageContextProviderFactory($api);

// Создаем фабрику для инструментов
$toolsFactory = new ToolsFactory($repositoryProvider, $pageContextProviderFactory);

// Создаем фабрику для сервиса инструментов
$toolServiceFactory = new ToolServiceFactory($toolsFactory);

// Создаем сервис инструментов с базовыми утилитами
$toolsService = $toolServiceFactory->withMainTools();

// Создаем маппер сообщений
$messageMapper = new ChatMapper();

// Создаем LMStudioClient
$lmStudioClient = new LMStudioClient(
    apiKey: getenv('OPENAI_API_KEY'),
    tools: $toolsService,
    messageMapper: $messageMapper
);

// Создаем чат с сообщением "привет"
$chat = new Chat();
$chat->addMessage(new UserMessage('привет'));

// Обрабатываем чат
$result = $lmStudioClient->process($chat);

echo "Результат: " . $result->getAnswer() . "\n";
echo "Токены: " . $result->getTotalTokens() . "\n";
