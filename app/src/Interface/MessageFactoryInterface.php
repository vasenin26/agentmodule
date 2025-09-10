<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\Conversation\Message;

interface MessageFactoryInterface
{
    public function createMessage(string $type, array $content): Message;
    public function getSupportedTypes(): array;
}
