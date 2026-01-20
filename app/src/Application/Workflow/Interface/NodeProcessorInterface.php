<?php

namespace Anymodule\Agentmodule\Application\Workflow\Interface;

use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

interface NodeProcessorInterface
{

    /**
     * @param Context $ctx
     * @return \Generator<StepResult>
     */
    public function process(Context $ctx): \Generator;
}