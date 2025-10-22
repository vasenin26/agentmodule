<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\GetCurrentBranch;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetCurrentBranchToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetCurrentBranch::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get current branch';

        $payload = $result['payload'] ?? null;
        $branch = $payload['branch'] ?? null;

        if(empty($branch)) {
            return 'No branch information found';
        }

        // Возвращаем только branch, убираем is_detached, remote_tracking
        return $branch;
    }
}
