<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\Support\AbstractEditorTool;
use Anymodule\Agentmodule\Application\Tools\Editor\Support\EditorException;
use Anymodule\Agentmodule\Application\Tools\Editor\Support\TextDocument;
use Anymodule\Agentmodule\Entity\ToolResult;

/**
 * Точечная правка файла заменой уникального фрагмента текста.
 * Не зависит от номеров строк, поэтому не ломается после предыдущих правок.
 */
class StrReplace extends AbstractEditorTool
{
    const NAME = 'editor-str-replace';

    private const MAX_REPORTED_MATCHES = 10;
    private const LINE_NUMBER_PREFIX = '/^\s*L?\d+(?::|\t|→|\s\|)/u';

    protected function handle(array $args): ToolResult
    {
        $url = $this->stringArg($args, 'url');
        $path = $this->stringArg($args, 'path');
        $old = TextDocument::normalize($this->stringArg($args, 'old_string'));
        $new = TextDocument::normalize($this->stringArg($args, 'new_string', allowEmpty: true));
        $replaceAll = $this->boolArg($args, 'replace_all');

        if ($old === $new) {
            throw new EditorException('old_string and new_string are identical — nothing to change.', 'NO_CHANGE');
        }

        $fullPath = $this->workspace->resolve($url, $path);
        $doc = $this->workspace->read($fullPath, $path);
        $count = substr_count($doc->content, $old);

        if ($count === 0) {
            throw $this->notFound($doc, $old, $path);
        }

        if ($count > 1 && !$replaceAll) {
            $lines = $this->matchLines($doc, $old);
            $shown = implode(', ', array_slice($lines, 0, self::MAX_REPORTED_MATCHES)) . ($count > self::MAX_REPORTED_MATCHES ? ', …' : '');

            throw new EditorException(
                "old_string matches $count locations in $path (lines $shown). "
                . 'Include more surrounding lines in old_string so it matches exactly one location, '
                . 'or set replace_all=true to replace every occurrence.',
                'MULTIPLE_MATCHES',
                ['match_lines' => $lines],
            );
        }

        $offset = strpos($doc->content, $old);
        $newContent = $replaceAll
            ? str_replace($old, $new, $doc->content)
            : substr_replace($doc->content, $new, $offset, strlen($old));

        $updated = $doc->withContent($newContent);
        $this->workspace->write($fullPath, $path, $updated->toRaw());

        // Текст до первого вхождения не изменился, поэтому его стартовая строка та же
        $startLine = $updated->lineAt($offset);
        $endLine = $startLine + max(0, substr_count(rtrim($new, "\n"), "\n"));

        $summary = $count === 1 ? 'replaced 1 occurrence' : "replaced all $count occurrences";
        $where = $count === 1 ? '' : ' (first occurrence)';

        return new ToolResult(true, "Edited $path: $summary. Updated snippet$where:\n" . $updated->snippet($startLine, $endLine), [
            'file_path' => $path,
            'replacements' => $count,
            'start_line' => $startLine,
            'end_line' => $endLine,
            'total_lines' => $updated->lineCount(),
        ]);
    }

    /**
     * @return int[] номера строк, где начинается каждое вхождение
     */
    private function matchLines(TextDocument $doc, string $needle): array
    {
        $lines = [];
        $offset = 0;

        while (($pos = strpos($doc->content, $needle, $offset)) !== false) {
            $lines[] = $doc->lineAt($pos);
            $offset = $pos + max(1, strlen($needle));
        }

        return $lines;
    }

    /**
     * Подсказывает модели, почему фрагмент не найден и как исправить запрос.
     */
    private function notFound(TextDocument $doc, string $old, string $path): EditorException
    {
        $oldLines = explode("\n", trim($old, "\n"));
        $nonEmpty = array_filter($oldLines, fn($l) => trim($l) !== '');

        if ($nonEmpty !== [] && count(preg_grep(self::LINE_NUMBER_PREFIX, $nonEmpty)) === count($nonEmpty)) {
            return new EditorException(
                "old_string was not found in $path. It seems to contain line-number prefixes copied from read output "
                . '(like "L12:" or "    12<TAB>"). Pass only the raw file text without line numbers.',
                'NOT_FOUND',
            );
        }

        $fileLines = $doc->lines();

        if ($match = $this->findIgnoringWhitespace($fileLines, $oldLines)) {
            [$start, $end] = $match;

            return new EditorException(
                "old_string was not found in $path exactly, but lines $start-$end match when whitespace/indentation is ignored. "
                . "Actual text is below — copy it exactly (without the line-number column) into old_string:\n"
                . $doc->snippet($start, $end, 0),
                'NOT_FOUND',
                ['similar_lines' => [$start, $end]],
            );
        }

        $hint = '';
        if ($closest = $this->findClosestLine($fileLines, $nonEmpty)) {
            $hint = "\nThe most similar text is near line $closest:\n"
                . $doc->snippet($closest, $closest + count($oldLines) - 1, 2);
        }

        return new EditorException(
            "old_string was not found in $path. It must match the file content exactly, including whitespace and indentation. "
            . 'The file may have changed since you last read it — re-read the relevant lines and retry.' . $hint,
            'NOT_FOUND',
        );
    }

    /**
     * @return array{int, int}|null диапазон строк (1-based), совпадающий без учёта пробелов
     */
    private function findIgnoringWhitespace(array $fileLines, array $oldLines): ?array
    {
        $squash = fn(string $s) => preg_replace('/\s+/u', ' ', trim($s));

        while ($oldLines !== [] && trim($oldLines[0]) === '') {
            array_shift($oldLines);
        }
        while ($oldLines !== [] && trim(end($oldLines)) === '') {
            array_pop($oldLines);
        }

        $needle = array_map($squash, $oldLines);
        $size = count($needle);

        if ($size === 0) {
            return null;
        }

        $haystack = array_map($squash, $fileLines);

        for ($i = 0, $max = count($haystack) - $size; $i <= $max; $i++) {
            if (array_slice($haystack, $i, $size) === $needle) {
                return [$i + 1, $i + $size];
            }
        }

        return null;
    }

    private function findClosestLine(array $fileLines, array $oldNonEmptyLines): ?int
    {
        $first = trim((string)reset($oldNonEmptyLines));

        if ($first === '' || strlen($first) > 300 || count($fileLines) > 10000) {
            return null;
        }

        $bestLine = null;
        $bestScore = 60.0;

        foreach ($fileLines as $idx => $line) {
            $candidate = trim($line);
            if ($candidate === '' || strlen($candidate) > 300) {
                continue;
            }
            similar_text($first, $candidate, $score);
            if ($score > $bestScore) {
                $bestScore = $score;
                $bestLine = $idx + 1;
            }
        }

        return $bestLine;
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => <<<DESC
Edit an existing file by replacing an exact text fragment. This is the preferred tool for any change to an existing file.

How it works:
- `old_string` must match the file content EXACTLY (every character, including indentation, blank lines and trailing spaces) and must be unique in the file.
- Include enough surrounding context (usually 2-5 lines) to make `old_string` unique. Keep it as short as possible otherwise.
- Never include line-number prefixes from read output in `old_string` or `new_string`.
- To delete code, pass an empty `new_string`.
- To rename a symbol everywhere in the file, set `replace_all` to true.
- For several separate changes in one file, call this tool once per change.

On success the result shows the edited region with line numbers, so you do not need to re-read the file to verify.
On failure the result explains why (not found / not unique) and shows the actual text to use.
DESC,
                'parameters' => [
                    'type' => 'object',
                    'properties' => self::repoParams() + [
                        'old_string' => [
                            'type' => 'string',
                            'description' => 'Exact text to replace, copied verbatim from the file. Must be unique unless replace_all is true.',
                        ],
                        'new_string' => [
                            'type' => 'string',
                            'description' => 'Text to put in place of old_string. Use the same indentation style as the file. Empty string deletes old_string.',
                        ],
                        'replace_all' => [
                            'type' => 'boolean',
                            'description' => 'Replace every occurrence of old_string instead of requiring a unique match. Default false.',
                        ],
                    ],
                    'required' => ['url', 'path', 'old_string', 'new_string'],
                    'additionalProperties' => false,
                ],
            ],
        ];
    }
}
