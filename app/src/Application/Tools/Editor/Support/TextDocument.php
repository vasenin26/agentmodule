<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor\Support;

/**
 * Текстовый файл в нормализованном виде: переводы строк приведены к "\n",
 * исходный стиль (LF/CRLF) и завершающий перевод строки восстанавливаются при записи.
 */
final class TextDocument
{
    private const SNIPPET_CONTEXT = 4;
    private const MAX_LINE_LENGTH = 500;

    private function __construct(
        public readonly string $content,
        public readonly string $eol,
    ) {
    }

    public static function fromRaw(string $raw): self
    {
        if (str_contains($raw, "\0")) {
            throw new EditorException('File looks binary (contains NUL bytes) and cannot be edited as text.', 'BINARY_FILE');
        }

        $eol = str_contains($raw, "\r\n") ? "\r\n" : "\n";

        return new self(self::normalize($raw), $eol);
    }

    public static function normalize(string $text): string
    {
        return str_replace("\r\n", "\n", $text);
    }

    public function withContent(string $content): self
    {
        return new self($content, $this->eol);
    }

    public function toRaw(): string
    {
        return $this->eol === "\n" ? $this->content : str_replace("\n", $this->eol, $this->content);
    }

    public function hasTrailingNewline(): bool
    {
        return str_ends_with($this->content, "\n");
    }

    /**
     * @return string[] строки без завершающего перевода строки
     */
    public function lines(): array
    {
        if ($this->content === '') {
            return [];
        }

        $body = $this->hasTrailingNewline() ? substr($this->content, 0, -1) : $this->content;

        return explode("\n", $body);
    }

    public function lineCount(): int
    {
        return count($this->lines());
    }

    /**
     * Номер строки (1-based), в которой находится байтовое смещение.
     */
    public function lineAt(int $offset): int
    {
        return substr_count($this->content, "\n", 0, $offset) + 1;
    }

    /**
     * Фрагмент файла с номерами строк в формате `cat -n` и небольшим контекстом вокруг.
     */
    public function snippet(int $startLine, int $endLine, int $context = self::SNIPPET_CONTEXT): string
    {
        $lines = $this->lines();
        $total = count($lines);

        if ($total === 0) {
            return '(file is empty)';
        }

        $from = max(1, $startLine - $context);
        $to = min($total, max($startLine, $endLine) + $context);
        $result = [];

        for ($n = $from; $n <= $to; $n++) {
            $line = $lines[$n - 1];
            if (mb_strlen($line) > self::MAX_LINE_LENGTH) {
                $line = mb_substr($line, 0, self::MAX_LINE_LENGTH) . '… [line truncated]';
            }
            $result[] = sprintf('%6d', $n) . "\t" . $line;
        }

        return implode("\n", $result);
    }
}
