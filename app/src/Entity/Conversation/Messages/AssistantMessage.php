<?php

namespace Anymodule\Agentmodule\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Message;

class SystemMessage implements Message
{
    public function __construct(private string $content)
    {
    }

    public function getContent(): string
    {
        return $this->content;
    }
}