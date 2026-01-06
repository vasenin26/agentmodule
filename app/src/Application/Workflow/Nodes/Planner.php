<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Application\Workflow\Interface\PlanableContextInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class Planner implements NodeProcessorInterface
{

    public function process(CodeContext|Context $ctx): StepResult
    {
        if($ctx instanceof PlanableContextInterface) {
            $ctx->setPlane([]);
        }

        return new StepResult(true);
    }
}