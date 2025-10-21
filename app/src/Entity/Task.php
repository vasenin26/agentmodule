<?php

namespace Anymodule\Agentmodule\Entity;

readonly class Task
{
    public function __construct(
        public int $id,
        public ?string $type,
        public int $conversationId,
        public array $messages,
        public array $context,
        public int $projectId,
        public bool $resultRequired,
    )
    {
    }
}