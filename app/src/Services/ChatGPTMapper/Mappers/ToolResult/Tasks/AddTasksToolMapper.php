<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Tasks;

use Anymodule\Agentmodule\Application\Tools\Tasks\AddTasks;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class AddTasksToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === AddTasks::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t add tasks';

        $payload = $result['payload'] ?? null;
        $tasks = $payload['tasks'] ?? null;

        if(empty($tasks)) {
            return 'No tasks added';
        }

        // Возвращаем только tasks, убираем stats
        return json_encode($tasks);
    }
}