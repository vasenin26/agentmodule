<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

interface CommandProxyClientInterface
{
    /**
     * @throws CommandProxyException on any transport-level failure.
     */
    public function send(array $request, int $timeoutSeconds): array;
}
