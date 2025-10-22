<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface;

use Vasenin26\Conversation\Message;

interface MessageMapperInterface
{
    public function supports(Message $message): bool;
    public function map(Message $message): array;
}