<?php

namespace Anymodule\Agentmodule\Application\Context;

use Anymodule\Agentmodule\Application\Workflow\Interface\CodeContextInterface;
use Anymodule\Agentmodule\Application\Workflow\Interface\PlanableContextInterface;
use Anymodule\Agentmodule\Application\Workflow\Interface\TesterContextInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

class CodeContext implements Context, PlanableContextInterface, CodeContextInterface, TesterContextInterface
{
    private ?array $plane = null;

    private bool $codeFinished = false;

    private bool $testFinished = false;
    private ?bool $testSuccess = null;

    public function __construct(
        readonly private Task        $task,
        readonly ContextConversation $conversation,
    )
    {
    }

    public function setPlane(array $tasks): void
    {
        $this->plane = $tasks;
    }

    public function hasPlane(): bool
    {
        return $this->plane !== null;
    }

    public function finishCode(): void
    {
        $this->codeFinished = true;
    }

    public function codeFinished(): bool
    {
        return $this->codeFinished;
    }

    public function setTestResult(bool $testResult): void
    {
        $this->testFinished = true;
        $this->testSuccess = $testResult;

        if($testResult === false) {
            $this->codeFinished = false;
        }
    }

    public function testSucceed(): bool
    {
        return $this->testSuccess === true;
    }

    public function testFinished(): bool
    {
        return $this->testFinished;
    }

    public function hasMessage(): bool
    {
        return $this->conversation->conversation->hasNoUserAnswer();
    }

    public function getProjectId(): int
    {
        return $this->task->projectId;
    }

    public function getTask(): Task
    {
        return $this->task;
    }

    public function getContextConversation(): ContextConversation
    {
        return $this->conversation;
    }

    public function getAvailableTools(): array
    {
        return [];
    }
}