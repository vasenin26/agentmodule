<?php

namespace Anymodule\Agentmodule\Entity;

readonly class ToolResult implements \Stringable, \JsonSerializable
{
    public function __construct(
        public bool   $status,
        public string $message,
        public array  $payload = [],
    )
    {
    }

    public function __toString(): string
    {
        return json_encode([
            'message' => $this->message,
            'payload' => $this->payload,
        ]);
    }

    public function jsonSerialize(): mixed
    {
        return [
            'message' => $this->message,
            'payload' => $this->payload,
        ];
    }
}