<?php

namespace Anymodule\Agentmodule\Interface;

use Vasenin26\Conversation\Message;

interface MessageFactoryInterface
{
    public function createMessage(string $type, array $content): Message;
    public function getSupportedTypes(): array;
}
