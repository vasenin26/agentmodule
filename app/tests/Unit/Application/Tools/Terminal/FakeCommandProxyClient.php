<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyClientInterface;
use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyException;

class FakeCommandProxyClient implements CommandProxyClientInterface
{
    /** @var array<int, array{request: array, timeout: int}> */
    public array $calls = [];

    public function __construct(
        private readonly ?array $response = null,
        private readonly ?CommandProxyException $exception = null,
    ) {
    }

    public function send(array $request, int $timeoutSeconds): array
    {
        $this->calls[] = ['request' => $request, 'timeout' => $timeoutSeconds];

        if ($this->exception !== null) {
            throw $this->exception;
        }

        return $this->response ?? [];
    }
}
