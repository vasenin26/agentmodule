<?php

namespace Anymodule\Agentmodule\Tools;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

class SendResult implements ToolInterface
{
    const NAME = 'result';

    public function execute(array $args): ?ToolResult
    {
        try {
            if (!array_key_exists('content', $args)) {
                return null; // отсутствие результата по семантике инструмента
            }

            $content = $args['content'];
            $message = 'Result captured';
            $payload = is_array($content) ? $content : ['content' => $content];
            return new ToolResult(true, $message, $payload);
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
