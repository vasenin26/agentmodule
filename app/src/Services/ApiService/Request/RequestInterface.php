<?php

namespace Anymodule\Agentmodule\Services\ApiService\Request;

use Anymodule\Agentmodule\Services\ApiService\ApiClient;
use Anymodule\Agentmodule\Services\ApiService\Response\ResponseInterface;

interface RequestInterface
{
    public function getMethod(): string;

    public function getUrl(): string;

    public function getPayload(): array;

    public function getToken(): ?string;

    public function exec(ApiClient $client): ResponseInterface;
}