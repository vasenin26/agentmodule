<?php

namespace Anymodule\Agentmodule\Application\Tools\Utils;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class UpdateArticle implements ToolInterface
{
    const NAME = 'update-article';

    private ?string $content = null;

    public function execute(array $args): ToolResult
    {
        $this->content = is_string($args['content'] ?? null) ? $args['content'] : null;

        return new ToolResult(true, 'Cодержимое успешно сохранено.');
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
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

    public function getName(): string
    {
        return self::NAME;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }
}
