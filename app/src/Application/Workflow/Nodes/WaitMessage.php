<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;

class WaitMessage implements NodeInterface
{

    public function process(Context $ctx): StepResult
    {
        // TODO: Implement process() method.
    }

    public function defineCurrentNode(Context $ctx)
    {
        // TODO: Implement defineCurrentNode() method.
    }

    public function getKey(): string
    {
        // TODO: Implement getKey() method.
    }
}