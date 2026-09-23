<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\Support\AbstractEditorTool;
use Anymodule\Agentmodule\Application\Tools\Editor\Support\EditorException;
use Anymodule\Agentmodule\Application\Tools\Editor\Support\TextDocument;
use Anymodule\Agentmodule\Entity\ToolResult;

/**
 * Вставка текста после указанной строки: начало файла, конец файла или место без уникального якоря.
 */
class Insert extends AbstractEditorTool
{
    const NAME = 'editor-insert';

    protected function handle(array $args): ToolResult
    {
        $url = $this->stringArg($args, 'url');
        $path = $this->stringArg($args, 'path');
        $insertLine = $this->intArg($args, 'insert_line');
        $text = TextDocument::normalize($this->stringArg($args, 'text'));

        $fullPath = $this->workspace->resolve($url, $path);
        $doc = $this->workspace->read($fullPath, $path);
        $lines = $doc->lines();
        $total = count($lines);

        if ($insertLine === -1) {
            $insertLine = $total;
        }

        if ($insertLine < 0 || $insertLine > $total) {
            throw new EditorException(
                "insert_line $insertLine is out of range: $path has $total lines. "
                . 'Use 0 to insert at the beginning, a value from 1 to ' . $total . ' to insert after that line, or -1 to append at the end.',
                'INVALID_LINE_NUMBER',
            );
        }

        // Завершающий перевод строки во вставке не должен порождать пустую строку
        $newLines = explode("\n", str_ends_with($text, "\n") ? substr($text, 0, -1) : $text);
        array_splice($lines, $insertLine, 0, $newLines);

        $trailingNewline = $total === 0 || $doc->hasTrailingNewline();
        $updated = $doc->withContent(implode("\n", $lines) . ($trailingNewline ? "\n" : ''));
        $this->workspace->write($fullPath, $path, $updated->toRaw());

        $start = $insertLine + 1;
        $end = $insertLine + count($newLines);
        $position = match (true) {
            $insertLine === 0 => 'at the beginning',
            $insertLine === $total => 'at the end',
            default => "after line $insertLine",
        };

        return new ToolResult(true, "Inserted " . count($newLines) . " line(s) into $path $position (now lines $start-$end). Updated snippet:\n" . $updated->snippet($start, $end), [
            'file_path' => $path,
            'lines_inserted' => count($newLines),
            'start_line' => $start,
            'end_line' => $end,
            'total_lines' => count($lines),
        ]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => <<<DESC
Insert text into an existing file after a given line number, without replacing anything.

Use it to add code at the beginning or end of a file (imports, new functions, config entries), or where editor-str-replace has no convenient unique anchor.
For changing existing code prefer editor-str-replace: line numbers shift after every edit, so take them from the most recent read or edit result.

The result shows the inserted lines with surrounding context and line numbers.
DESC,
                'parameters' => [
                    'type' => 'object',
                    'properties' => self::repoParams() + [
                        'insert_line' => [
                            'type' => 'integer',
                            'description' => 'Line number (1-based) AFTER which the text is inserted. 0 inserts at the beginning of the file, -1 appends at the end.',
                        ],
                        'text' => [
                            'type' => 'string',
                            'description' => 'Text to insert, may span multiple lines. Use the same indentation as the surrounding code.',
                        ],
                    ],
                    'required' => ['url', 'path', 'insert_line', 'text'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
