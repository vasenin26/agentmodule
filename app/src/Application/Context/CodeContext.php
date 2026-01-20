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
    public function __construct(
        readonly private Task        $task,
        readonly ContextConversation $conversation,
    )
    {
    }

    private const PAYLOAD_NS = 'code';

    private function &getCodePayload(): array
    {
        $payload =& $this->conversation->context->payload;
        if (!isset($payload[self::PAYLOAD_NS]) || !is_array($payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS] = [];
        }

        if (!array_key_exists('plane', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['plane'] = null;
        }
        if (!array_key_exists('codeFinished', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['codeFinished'] = false;
        }
        if (!array_key_exists('testFinished', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['testFinished'] = false;
        }
        if (!array_key_exists('testSuccess', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['testSuccess'] = null;
        }

        return $payload[self::PAYLOAD_NS];
    }

    /**
     * Switch state to "development in progress":
     * - codeFinished=false
     * - testFinished=false
     * - testSuccess=null
     */
    public function startDevelopment(): void
    {
        $code =& $this->getCodePayload();
        $code['codeFinished'] = false;
        $code['testFinished'] = false;
        $code['testSuccess'] = null;
    }

    /**
     * Switch state to "ready for testing":
     * - codeFinished=true
     * - testFinished=false
     * - testSuccess=null
     */
    public function startTesting(): void
    {
        $code =& $this->getCodePayload();
        $code['codeFinished'] = true;
        $code['testFinished'] = false;
        $code['testSuccess'] = null;
    }

    public function setPlane(array $tasks): void
    {
        $code =& $this->getCodePayload();
        $code['plane'] = $tasks;
    }

    public function hasPlane(): bool
    {
        $code = $this->getCodePayload();
        return $code['plane'] !== null;
    }

    public function finishCode(): void
    {
        $code =& $this->getCodePayload();
        $code['codeFinished'] = true;
    }

    public function codeFinished(): bool
    {
        $code = $this->getCodePayload();
        return $code['codeFinished'] === true;
    }

    public function setTestResult(bool $testResult): void
    {
        $code =& $this->getCodePayload();
        $code['testFinished'] = true;
        $code['testSuccess'] = $testResult;

        if ($testResult === false) {
            $code['codeFinished'] = false;
        }
    }

    public function testSucceed(): bool
    {
        $code = $this->getCodePayload();
        return $code['testSuccess'] === true;
    }

    public function testFinished(): bool
    {
        $code = $this->getCodePayload();
        return $code['testFinished'] === true;
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