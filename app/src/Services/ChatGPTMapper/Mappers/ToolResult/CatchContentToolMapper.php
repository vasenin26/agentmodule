<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult;

use Anymodule\Agentmodule\Application\Tools\CatchContent;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class CatchContentToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === CatchContent::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t catch content';

        // Возвращаем только подтверждение, так как CatchContent не возвращает полезных данных
        return 'Content captured successfully';
    }
}
