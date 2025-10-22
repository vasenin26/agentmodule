<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Page\GetTaskHistory;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetTaskHistoryToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetTaskHistory::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get task history';

        $payload = $result['payload'] ?? null;
        $history = $payload['history'] ?? null;

        if(empty($history)) {
            return 'No task history found';
        }

        // Возвращаем только history, убираем page_id, total_entries
        return json_encode($history);
    }
}
