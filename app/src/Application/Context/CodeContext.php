<?php

namespace Anymodule\Agentmodule\Application\Context;

use Anymodule\Agentmodule\Application\Workflow\Interface\PlanableContextInterface;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class CodeContext implements Context, PlanableContextInterface
{
    public function __construct(
        Task $task,
    )
    {
    }

    public function hasPlane(): bool
    {
    }

    public function codeFinished(): bool
    {
    }

    public function testFailed(): bool
    {
    }

    public function testSucceed(): bool
    {
    }

    public function hasMessage(): bool
    {
    }

    public function setPlane(array $tasks): void
    {
    }
}