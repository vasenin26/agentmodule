<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\Commit;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class CommitToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === Commit::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t commit';

        $payload = $result['payload'] ?? null;
        $commitHash = $payload['commit_hash'] ?? null;

        if(empty($commitHash)) {
            return 'Commit completed successfully';
        }

        // Возвращаем только commit_hash, убираем message, author, timestamp
        return $commitHash;
    }
}
