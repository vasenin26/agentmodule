<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\CallToolMessage;

class CallToolMapper implements MessageMapperInterface
{
    public function __construct(
        private ToolsProviderService $toolsService
    ) {}

    public function supports(Message $message): bool
    {
        return $message instanceof CallToolMessage;
    }

    public function map(Message $message): array
    {
        if (!$message instanceof CallToolMessage) {
            throw new \Exception("Unsupported message type");
        }

        try {
            $result = $this->toolsService->callTool(
                $message->name, 
                json_encode($message->args)
            );
            
            $content = "{$message->description}\n[РЕЗУЛЬТАТ ВЫПОЛНЕНИЯ ФУНКЦИИ: {$message->name}]\n\n{$result}";
        } catch (\Exception $e) {
            $content = "{$message->description}\n[ОШИБКА ВЫПОЛНЕНИЯ ФУНКЦИИ: {$message->name}]\n\nОшибка: {$e->getMessage()}";
        }

        return [
            'role' => 'user',
            'content' => $content,
        ];
    }
}
