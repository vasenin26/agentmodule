<?php

namespace Anymodule\Agentmodule\Services\ApiService;

use PHPUnit\Event\Code\Throwable;

class Response
{
    public function __construct(
        readonly public int $code,
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

    public function getError(): string
    {
        $data = $this->getData();
        $message = $data["message"];

        return $message;
    }
}