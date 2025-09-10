<?php

namespace Anymodule\Agentmodule\Entity\Conversation;

interface Message
{
    public function getContent(): array;
    public function getType(): string;
    public static function createFromData(array $content): self;
}