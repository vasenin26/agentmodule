<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\UnstageFile;
use Vasenin26\Conversation\Messages\ToolMessage;

class UnstageFileToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === UnstageFile::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t unstage file';

        // Возвращаем только подтверждение для git операций
        return 'File unstaged successfully';
    }
}
