<?php

namespace Anymodule\Agentmodule\Application\Tools\Workflow;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

final class SwitchToDevelopment implements ToolInterface
{
    public const NAME = 'workflow-switch-to-development';

    public function __construct(
        private CodeContext $ctx
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        $this->ctx->requestDevelopment();

        return new ToolResult(true, 'Switched context to development', [
            'state' => [
                'codeFinished' => false,
                'testFinished' => false,
            ],
            'instruction' => 'Proceed with code changes (development).',
        ]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Switch workflow context to development. '
                    . 'Use when the user says "продолжай", "давай дальше", "continue", or asks to keep implementing/fixing code.',
            ],
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}

