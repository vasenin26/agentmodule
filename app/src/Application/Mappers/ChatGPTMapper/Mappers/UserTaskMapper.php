<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\MessageMapperInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\UserTaskMessage;

class UserTaskMapper implements MessageMapperInterface
{

    public function supports(Message $message): bool
    {
        return $message instanceof UserTaskMessage;
    }

    public function map(Message $message): array
    {
        if($message instanceof UserTaskMessage) {
            return [
                'role' => 'user',
                'content' => $message->content,
            ];
        }

        throw new \Exception("Unsupported message type");
    }
}


