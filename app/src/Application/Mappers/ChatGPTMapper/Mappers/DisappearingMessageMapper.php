<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\MessageMapperInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\DisappearingMessage;

class DisappearingMessageMapper implements MessageMapperInterface
{

    public function supports(Message $message): bool
    {
        return $message instanceof DisappearingMessage;
    }

    public function map(Message $message): array
    {
        if($message instanceof DisappearingMessage) {
            return [
                'role' => 'user',
                'content' => $message->content,
            ];
        }

        throw new \Exception("Unsupported message type");
    }
}


