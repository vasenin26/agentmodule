<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\Push;
use Vasenin26\Conversation\Messages\ToolMessage;

class PushToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === Push::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t push';

        $payload = $result['payload'] ?? null;
        $output = $payload['output'] ?? null;

        if(empty($output)) {
            return 'Push completed successfully';
        }

        // Возвращаем только output, убираем branch, remote, commits_pushed
        return $output;
    }
}
