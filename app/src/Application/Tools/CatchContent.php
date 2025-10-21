<?php

namespace Anymodule\Agentmodule\Application\Tools;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class CatchContent implements ToolInterface
{
    const NAME = 'catch-content';

    private string $content = '';

    public function __construct(
        private string $name,
        private string $description,
        private string $message,
    )
    {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            if (!array_key_exists('content', $args)) {
                return null; // отсутствие результата по семантике инструмента
            }

            $this->content = $args['content'] ?? '';

            return new ToolResult(true, $this->message, []);
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
                'description' => $this->description,
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
        return $this->name;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function hasContent(): bool
    {
        return !empty($this->content);
    }
}