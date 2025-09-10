<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Interface;

use Anymodule\Agentmodule\Entity\Conversation\Message;

interface MessageMapperInterface
{
    public function supports(Message $message): bool;
    public function map(Message $message): array;
}