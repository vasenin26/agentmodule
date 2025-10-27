<?php

namespace Anymodule\Agentmodule\Interface;

use Ramsey\Uuid\UuidInterface;

interface AgentMetaProviderInterface
{

    public function getAgentUuid(): UuidInterface;

    public function getDefaultModel(): string;
}