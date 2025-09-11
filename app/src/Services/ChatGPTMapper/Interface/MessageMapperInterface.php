<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Interface;

use Vasenin26\Conversation\Message;

interface MessageMapperInterface
{
    public function supports(Message $message): bool;
    public function map(Message $message): array;
}