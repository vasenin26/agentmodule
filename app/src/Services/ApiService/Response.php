<?php

namespace Anymodule\Agentmodule\Services\ApiService;

class Response
{
    public function __construct(
        private string $body
    )
    {
    }

    public function getBody(): string
    {
        return $this->body;
    }

    public function getData(): array
    {
        return json_decode($this->body, true);
    }
}