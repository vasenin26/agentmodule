<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\GetStatus;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetStatusToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetStatus::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get status';

        $payload = $result['payload'] ?? null;
        $status = $payload['status'] ?? null;

        if(empty($status)) {
            return 'No status information found';
        }

        // Возвращаем только status, убираем branch, clean, ahead, behind
        return $status;
    }
}
