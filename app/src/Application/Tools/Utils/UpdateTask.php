<?php

namespace Anymodule\Agentmodule\Application\Tools\Utils;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class UpdateTask implements ToolInterface
{
    const NAME = 'update-article';


    private ?string $title = null;
    private ?string $content = null;

    public function execute(array $args): ToolResult
    {
        $this->title = is_string($args['title'] ?? null) ? $args['title'] : null;
        $this->content = is_string($args['content'] ?? null) ? $args['content'] : null;

        return new ToolResult(true, 'Cодержимое успешно сохранено.');
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Сохраняет содержимое задачи в хранилище. Если поле не заполнено значение для не будет обновлено в хранилище',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'title' => [
                            'type' => 'string',
                            'description' => 'Заголовок без дополнительных сведений.',
                        ],
                        'content' => [
                            'type' => 'string',
                            'description' => 'Описание задачи.',
                        ]
                    ]
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContent(): ?string
    {
        return json_encode([
            'title' => $this->title,
            'content' => $this->content,
        ]);
    }

    public function hasContent(): bool
    {
        return $this->title !== null || $this->content !== null;
    }

    public function flush(): void
    {
        $this->title = null;
        $this->content = null;
    }
}
