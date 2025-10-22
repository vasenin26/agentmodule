<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\ResetHard;
use Vasenin26\Conversation\Messages\ToolMessage;

class ResetHardToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === ResetHard::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t reset hard';

        // Возвращаем только подтверждение для git операций
        return 'Reset hard completed successfully';
    }
}
