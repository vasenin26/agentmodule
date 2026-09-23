<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\Support\AbstractEditorTool;
use Anymodule\Agentmodule\Application\Tools\Editor\Support\TextDocument;
use Anymodule\Agentmodule\Entity\ToolResult;

/**
 * Создание нового файла или полная перезапись существующего.
 */
class WriteFile extends AbstractEditorTool
{
    const NAME = 'editor-write-file';

    protected function handle(array $args): ToolResult
    {
        $url = $this->stringArg($args, 'url');
        $path = $this->stringArg($args, 'path');
        $content = $this->stringArg($args, 'content', allowEmpty: true);

        $fullPath = $this->workspace->resolve($url, $path);
        $existing = file_exists($fullPath) ? $this->workspace->read($fullPath, $path) : null;

        // Существующий файл сохраняет свой стиль переводов строк
        $doc = ($existing ?? TextDocument::fromRaw(''))->withContent(TextDocument::normalize($content));
        $lineCount = $doc->lineCount();

        if ($existing !== null && $existing->content === $doc->content) {
            return new ToolResult(true, "$path already has exactly this content — no changes made.", [
                'file_path' => $path,
                'changed' => false,
                'total_lines' => $lineCount,
            ]);
        }

        $this->workspace->write($fullPath, $path, $doc->toRaw());

        $message = $existing === null
            ? "Created $path ($lineCount lines)."
            : "Overwrote $path ($lineCount lines, previously {$existing->lineCount()}).";

        return new ToolResult(true, $message, [
            'file_path' => $path,
            'created' => $existing === null,
            'total_lines' => $lineCount,
        ]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => <<<DESC
Create a new file, or completely overwrite an existing file, with the given content. Missing parent directories are created automatically.

Use it for new files, or when rewriting most of a small file.
For changes to an existing file prefer editor-str-replace: it is cheaper and cannot accidentally drop unrelated code.
Before overwriting an existing file, read it first so no content is lost.
DESC,
                'parameters' => [
                    'type' => 'object',
                    'properties' => self::repoParams() + [
                        'content' => [
                            'type' => 'string',
                            'description' => 'Complete file content. End it with a newline.',
                        ],
                    ],
                    'required' => ['url', 'path', 'content'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
