<?php

namespace Anymodule\Agentmodule\Application\Tools;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class PatchContent implements ToolInterface
{
    const NAME = 'patch-content';

    private string $title = '';
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
            if (!array_key_exists('content', $args) || !array_key_exists('title', $args)) {
                return null;
            }

            $this->content = $args['content'] ?? '';
            $this->title = $args['title'] ?? '';

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
                        'title' => [
                            'type' => 'string',
                            'description' => 'Короткое название предлагаемых изменения (50 символов)',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => <<<DESC
Текст изменений в формате патча (Unified diff / git-style).  
Нужно сгенерировать только diff между текущей версией файла и новой, без полного переписывания.  
Формат патча должен быть такой:

@@ <context> @@
-строка_до_изменения
+строка_после_изменения


Никаких объяснений или комментариев вне патча добавлять не нужно.
DESC
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

    public function getPatch(): string
    {
        return json_encode([
            'title' => $this->title,
            'content' => $this->content,
        ]);
    }

    public function hasContent(): bool
    {
        return !empty($this->content) && !empty($this->title);
    }
}