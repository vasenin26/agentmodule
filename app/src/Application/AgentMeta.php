<?php

namespace Anymodule\Agentmodule\Application;

use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Ramsey\Uuid\UuidInterface;

class AgentMeta implements AgentMetaProviderInterface
{
    public function __construct(
        private UuidInterface $agetUuid,
    )
    {
    }

    public function getAgentUuid(): UuidInterface
    {
        return $this->agetUuid;
    }
}