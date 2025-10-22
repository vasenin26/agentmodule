<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ReplaceInFile implements ToolInterface
{
    const NAME = 'editor-replace-in-file';
    /**
     * Perform simple string replacement with whitespace preservation.
     * For method chaining patterns (starting with ->), preserves leading whitespace.
     * For other patterns, preserves leading whitespace using $1 placeholder.
     */
    private function performStringReplacement(string $content, string $search, string $replacement): array
    {
        $replacements = 0;
        $newContent = $content;
        
        // For method chaining patterns (starting with ->), preserve leading whitespace
        if (strpos($search, '->') === 0) {
            // Find all occurrences and preserve their leading whitespace
            $lines = explode("\n", $content);
            $modified = false;
            
            foreach ($lines as $lineIndex => $line) {
                if (strpos($line, $search) !== false) {
                    // Find the position of the search string
                    $pos = strpos($line, $search);
                    // Extract leading whitespace
                    $leadingWhitespace = substr($line, 0, $pos);
                    // Create replacement with preserved whitespace
                    $newLine = $leadingWhitespace . $replacement;
                    $lines[$lineIndex] = $newLine;
                    $replacements++;
                    $modified = true;
                }
            }
            
            if ($modified) {
                $newContent = implode("\n", $lines);
            }
        } else {
            // For regular patterns, use simple string replacement
            $newContent = str_replace($search, $replacement, $content, $replacements);
        }
        
        return [$newContent, $replacements];
    }
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'path' => $path, 'pattern' => $pattern, 'replacement' => $replacement] = $args;

            // Basic input validation and normalization
            if (!is_string($url) || $url === '' || !is_string($path) || $path === '') {
                return new ToolResult(false, 'Invalid arguments: url and path must be non-empty strings', ['code' => 'ARGUMENTS_INVALID']);
            }

            if (!is_string($pattern) || $pattern === '') {
                return new ToolResult(false, 'Invalid arguments: pattern must be a non-empty string', ['code' => 'PATTERN_INVALID']);
            }

            if (!is_string($replacement)) {
                // Normalize non-string replacements to string to avoid deprecations/warnings
                $replacement = (string) $replacement;
            }

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return new ToolResult(false, 'Repository not found', ['code' => 'REPO_NOT_FOUND']);
            }

            $fullFilePath = $repoPath . '/' . trim($path, '/');

            // Проверяем, что файл существует
            if (!file_exists($fullFilePath)) {
                return new ToolResult(false, 'File not found: ' . $path, ['code' => 'FILE_NOT_FOUND']);
            }

            // Проверяем, что файл доступен для записи
            if (!is_writable($fullFilePath)) {
                return new ToolResult(false, 'File is not writable: ' . $path, ['code' => 'FILE_NOT_WRITABLE']);
            }

            // Читаем содержимое файла
            $content = file_get_contents($fullFilePath);
            if ($content === false) {
                return new ToolResult(false, 'Failed to read file: ' . $path, ['code' => 'READ_FAILED']);
            }

            // Выполняем простую замену строк
            $originalContent = $content;
            [$newContent, $replacementCount] = $this->performStringReplacement($content, $pattern, $replacement);

            // Проверяем, была ли выполнена замена
            if ($originalContent === $newContent) {
                
                return new ToolResult(true, 'No matches found for pattern', [
                    'file_path' => $path,
                    'pattern' => $pattern,
                    'replacement' => $replacement,
                    'replacements_made' => 0,
                ]);
            }

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $newContent);

            if ($result === false) {
                return new ToolResult(false, 'Failed to write content to file: ' . $path, ['code' => 'WRITE_FAILED']);
            }

            return new ToolResult(true, 'File updated successfully', [
                'file_path' => $path,
                'pattern' => $pattern,
                'replacement' => $replacement,
                'replacements_made' => $replacementCount,
                'bytes_written' => $result,
                'content_length' => strlen((string) $newContent),
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to replace in file: ' . $e->getMessage(), ['code' => 'REPLACE_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Replace text in file using simple string matching. Searches for exact string matches and replaces them with the specified replacement text. For method chaining patterns (starting with ->), automatically preserves leading whitespace. Example: search for "хочу" and replace with "не хочу" in "я хочу какать" to get "я не хочу какать".',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository url',
                        ],
                        'path' => [
                            'type' => 'string',
                            'description' => 'Path to file',
                        ],
                        'pattern' => [
                            'type' => 'string',
                            'description' => 'Exact string to search for. Will be replaced with the replacement text. For method chaining patterns (starting with ->), leading whitespace is automatically preserved. Example: "->orderBy(\'created_at\', \'desc\')" to find and replace method calls.',
                        ],
                        'replacement' => [
                            'type' => 'string',
                            'description' => 'Replacement text. For method chaining patterns (starting with ->), leading whitespace is automatically preserved. For other patterns, use exact replacement text.',
                        ],
                    ],
                    'required' => ['url', 'path', 'pattern', 'replacement'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
