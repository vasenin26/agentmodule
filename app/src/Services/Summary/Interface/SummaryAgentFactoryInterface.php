<?php

namespace Anymodule\Agentmodule\Services\Summary\Interface;

use Anymodule\Agentmodule\Interface\ActionContract;

interface SummaryAgentFactoryInterface
{

    public function createSummaryAgent(): ActionContract;
}