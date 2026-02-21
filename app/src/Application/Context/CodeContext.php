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
        if (!array_key_exists('devRound', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['devRound'] = 0;
        }
        if (!array_key_exists('testedRound', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['testedRound'] = 0;
        }
        if (!array_key_exists('testResult', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['testResult'] = null;
        }
        if (!array_key_exists('requestedTransition', $payload[self::PAYLOAD_NS])) {
            $payload[self::PAYLOAD_NS]['requestedTransition'] = null;
        }

        return $payload[self::PAYLOAD_NS];
    }

    public function incrementDevRound(): void
    {
        $code =& $this->getCodePayload();
        $code['devRound'] = ($code['devRound'] ?? 0) + 1;
    }

    public function devRound(): int
    {
        $code = $this->getCodePayload();
        return (int) ($code['devRound'] ?? 0);
    }

    public function setTestedRound(int $round): void
    {
        $code =& $this->getCodePayload();
        $code['testedRound'] = $round;
    }

    public function testedRound(): int
    {
        $code = $this->getCodePayload();
        return (int) ($code['testedRound'] ?? 0);
    }

    public function setTestResult(bool $result): void
    {
        $code =& $this->getCodePayload();
        $code['testResult'] = $result;
    }

    public function lastTestResult(): ?bool
    {
        $code = $this->getCodePayload();
        $v = $code['testResult'] ?? null;
        return $v === null ? null : (bool) $v;
    }

    public function requestTransition(string $to): void
    {
        $code =& $this->getCodePayload();
        $code['requestedTransition'] = $to;
    }

    public function getRequestedTransition(): ?string
    {
        $code = $this->getCodePayload();
        $v = $code['requestedTransition'] ?? null;
        return $v === null || $v === '' ? null : (string) $v;
    }

    public function clearRequestedTransition(): void
    {
        $code =& $this->getCodePayload();
        $code['requestedTransition'] = null;
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
