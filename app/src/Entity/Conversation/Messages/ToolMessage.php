<?php

namespace Anymodule\Agentmodule\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Message;

readonly class ToolMessage implements Message
{
    public function __construct(
        public string $id,
        public string $name,
        public string $result,
    )
    {
    }

    public function getContent(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'result' => $this->result
        ];
    }

    public function getType(): string
    {
        return 'tool';
    }
    
    public static function createFromData(array $content): self
    {
        return new self(
            $content['id'],
            $content['name'],
            $content['result']
        );
    }
}