<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Terminal;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Terminal\Peek;
use Vasenin26\Conversation\Messages\ToolMessage;

class TerminalPeekToolMapper implements ToolMapperInterface
{
    use TerminalPollToolMapperTrait;

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === Peek::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if (!$message->success) {
            return $result['message'] ?? 'Failed to peek at job';
        }

        return $this->mapPollPayload($result['payload'] ?? []);
    }
}
