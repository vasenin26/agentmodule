<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Entity\Conversation\Message;
use Anymodule\Agentmodule\Entity\Conversation\Messages\SystemMessage;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;

class SystemMapper implements MessageMapperInterface
{

    public function supports(Message $message): bool
    {
        return $message instanceof SystemMessage;
    }

    public function map(Message $message): array
    {
        if($message instanceof SystemMessage) {
            return [
                'role' => 'system',
                'content' => $message->content,
            ];
        }

        throw new \Exception("Unsupported message type");
    }
}