<?php

namespace Anymodule\Agentmodule\Tools;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class SendResult implements ToolInterface
{
    const NAME = 'result';

    public function execute(array $args): ?string
    {
        return $args['content'] ?? null;
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Сохраняет данные в хранилище.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'content' => [
                            'type' => 'string',
                            'description' => 'Данные для сохранения.',
                        ]
                    ],
                    'required' => ['content'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
