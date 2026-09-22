<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Terminal;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Terminal\Wait;
use Vasenin26\Conversation\Messages\ToolMessage;

class TerminalWaitToolMapper implements ToolMapperInterface
{
    use TerminalPollToolMapperTrait;

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === Wait::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if (!$message->success) {
            return $result['message'] ?? 'Failed to wait on job';
        }

        return $this->mapPollPayload($result['payload'] ?? []);
    }
}
