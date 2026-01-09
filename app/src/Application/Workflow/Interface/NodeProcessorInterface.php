<?php

namespace Anymodule\Agentmodule\Application\Workflow\Interface;

use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

interface NodeProcessorInterface
{

    public function process(Context $ctx): \Generator;
}