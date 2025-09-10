<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Entity\Conversation\Message;
use Anymodule\Agentmodule\Entity\Conversation\Messages\UserMessage;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;

class UserMapper implements MessageMapperInterface
{

    public function can(Message $message): bool
    {
        return $message instanceof UserMessage;
    }

    public function map(Message $message): array
    {
        return [
            'role' => 'user',
            'content' => $message->getContent(),
        ];
    }
}