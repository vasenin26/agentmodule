<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;

class WaitMessage implements NodeProcessorInterface
{

    public function process(Context $ctx): \Generator
    {
        yield new StepResult(true);
    }
}