<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools;


use Anymodule\Agentmodule\Interface\ToolInterface;

class CurrentTime implements ToolInterface
{

    public function execute(array $args): string
    {
        return (new \DateTime())->format('Y-m-d H:i:s');
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Return current date and time',
            ]
        ];
    }
}
