<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult;

use Anymodule\Agentmodule\Application\Tools\SendResult;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class SendResultToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === SendResult::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t send result';

        $payload = $result['payload'] ?? null;
        
        // Возвращаем только content, убираем все остальные поля
        if (isset($payload['content'])) {
            return is_string($payload['content']) ? $payload['content'] : json_encode($payload['content']);
        }

        return 'Result sent successfully';
    }
}
