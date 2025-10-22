<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\MessageMapperInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\UserMessage;

class UserMapper implements MessageMapperInterface
{

    public function supports(Message $message): bool
    {
        return $message instanceof UserMessage;
    }

    public function map(Message $message): array
    {
        if($message instanceof UserMessage) {
            return [
                'role' => 'user',
                'content' => $message->content,
            ];
        }

        throw new \Exception("Unsupported message type");
    }
}