<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\Pull;
use Vasenin26\Conversation\Messages\ToolMessage;

class PullToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === Pull::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t pull';

        $payload = $result['payload'] ?? null;
        $output = $payload['output'] ?? null;

        if(empty($output)) {
            return 'Pull completed successfully';
        }

        // Возвращаем только output, убираем branch, remote, commits_ahead, commits_behind
        return $output;
    }
}
