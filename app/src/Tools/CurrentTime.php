<?php

namespace Anymodule\Agentmodule\Tools;


use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class CurrentTime implements ToolInterface
{
    const NAME = 'current-time';

    public function execute(array $args): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s');
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
