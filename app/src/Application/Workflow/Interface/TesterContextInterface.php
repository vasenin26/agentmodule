<?php

namespace Anymodule\Agentmodule\Application\Workflow\Interface;

use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

interface TesterContextInterface extends Context
{
    public function devRound(): int;

    public function setTestedRound(int $round): void;

    public function testedRound(): int;

    public function setTestResult(bool $testResult): void;

    public function lastTestResult(): ?bool;
}
