<?php

namespace Anymodule\Agentmodule\Tools\Utils;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class UpdateArticle implements ToolInterface
{
    private ?string $content = null;

    public function execute(array $args): ?string
    {
        $this->content = is_string($args['content'] ?? null) ? $args['content'] : null;

        return 'Cодержимое успешно сохранено.';
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Сохраняет содержимое статьи в хранилище.',
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

    public function getContent(): ?string
    {
        return $this->content;
    }
}
