<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Tasks;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Tasks\ListTasks;
use Vasenin26\Conversation\Messages\ToolMessage;

class ListTasksToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === ListTasks::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t list tasks';

        $payload = $result['payload'] ?? null;
        $tasks = $payload['tasks'] ?? null;

        if(empty($tasks)) {
            return 'No tasks found';
        }

        // Возвращаем только tasks, убираем stats
        return json_encode($tasks);
    }
}