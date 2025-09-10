<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Entity\Conversation\Message;
use Anymodule\Agentmodule\Entity\Conversation\Messages\ToolMessage;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;

class ToolMapper implements MessageMapperInterface
{

    public function supports(Message $message): bool
    {
        if ($message instanceof ToolMessage) {
            return !empty($message->id);
        }

        return false;
    }

    public function map(Message $message): array
    {
        if($message instanceof ToolMessage) {
            return [
                'role' => 'tool',
                'tool_call_id' => $message->id,
                'content' => $message->result,
            ];
        }

        throw new \Exception("Unsupported message type");
    }
}