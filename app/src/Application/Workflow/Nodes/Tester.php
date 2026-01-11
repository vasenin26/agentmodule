<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Application\Workflow\Interface\TesterContextInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class Tester implements NodeProcessorInterface
{

    public function process(TesterContextInterface|Context $ctx): \Generator
    {
        if($ctx instanceof TesterContextInterface) {
            $ctx->setTestResult(true);
        }

        yield new StepResult(true);
    }
}