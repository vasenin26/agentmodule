<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\MessageMapperInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\SystemMessage;

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