<?php

namespace Anymodule\Agentmodule\Application\Tools;


use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class CurrentTime implements ToolInterface
{
    const NAME = 'current-time';

    public function execute(array $args): ?ToolResult
    {
        try {
            $now = (new \DateTime())->format('Y-m-d H:i:s');
            return new ToolResult(true, 'Current time generated', ['datetime' => $now]);
        } catch (\Throwable $e) {
            return new ToolResult(false, $e->getMessage(), ['exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Return current date and time',
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
