<?php

namespace Anymodule\Agentmodule\Application\Tools\Workflow;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

final class SwitchToTesting implements ToolInterface
{
    public const NAME = 'workflow-switch-to-testing';

    public function __construct(
        private CodeContext $ctx
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        $this->ctx->startTesting();

        return new ToolResult(true, 'Switched context to testing', [
            'state' => [
                'codeFinished' => true,
                'testFinished' => false,
            ],
            'instruction' => 'Proceed with testing (run tests, report results).',
        ]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Switch workflow context to testing. '
                    . 'Use when the user says "протестируй", "запусти тесты", "run tests", or asks to verify/test the implementation.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => new \stdClass(),
                ],
            ],
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

