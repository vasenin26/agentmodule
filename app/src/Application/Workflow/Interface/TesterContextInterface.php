<?php

namespace Anymodule\Agentmodule\Application\Workflow\Interface;

use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

interface TesterContextInterface extends Context
{
    public function setTestResult(bool $testResult): void;
}