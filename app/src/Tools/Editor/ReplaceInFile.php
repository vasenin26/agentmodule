<?php

namespace Anymodule\Agentmodule\Tools\Editor;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ReplaceInFile implements ToolInterface
{
    const NAME = 'editor-replace-in-file';
    /**
     * Normalize a user-provided regex pattern.
     * - If the pattern already contains valid delimiters with optional modifiers at the end, return as-is.
     * - Otherwise, wrap with '~' delimiters and add default 'su' modifiers for multiline + unicode.
     * The function does not escape the body, assuming caller wants a regex (not a literal text).
     */
    private function normalizePattern(string $pattern): string
    {
        // Detect if pattern is already delimited: ^(delimiter).*\1(modifiers)?$
        // Allowed delimiters here: ~ # / @ % ! ; ` \|
        // Use a conservative check to avoid false positives.
        $isDelimited = (bool) preg_match('/^([~#\/@%!;`\|]).*\1[imsxuADSUXJ]*$/', $pattern);

        if ($isDelimited) {
            return $pattern;
        }

        // Default: wrap as ~...~su so dot matches newlines and unicode is enabled
        return '~' . $pattern . '~su';
    }
    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?string
    {
        try {
            ['url' => $url, 'path' => $path, 'pattern' => $pattern, 'replacement' => $replacement, 'create_if_not_exists' => $createIfNotExists] = $args + ['create_if_not_exists' => false];

            // Basic input validation and normalization
            if (!is_string($url) || $url === '' || !is_string($path) || $path === '') {
                return json_encode([
                    'success' => false,
                    'error' => 'Invalid arguments: url and path must be non-empty strings',
                    'code' => 'ARGUMENTS_INVALID',
                ]);
            }

            if (!is_string($pattern) || $pattern === '') {
                return json_encode([
                    'success' => false,
                    'error' => 'Invalid arguments: pattern must be a non-empty string',
                    'code' => 'PATTERN_INVALID',
                ]);
            }

            if (!is_string($replacement)) {
                // Normalize non-string replacements to string to avoid deprecations/warnings
                $replacement = (string) $replacement;
            }

            $repo = $this->repoProvider->getRepo($url);
            $repoPath = $repo->getRepositoryPath();

            if (!is_dir($repoPath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'Repository not found',
                    'code' => 'REPO_NOT_FOUND',
                ]);
            }

            $fullFilePath = $repoPath . '/' . trim($path, '/');

            // Проверяем, что файл существует
            if (!file_exists($fullFilePath)) {
                if ($createIfNotExists) {
                    // Создаем директорию если она не существует
                    $dir = dirname($fullFilePath);
                    if (!is_dir($dir)) {
                        if (!mkdir($dir, 0755, true)) {
                            return json_encode([
                                'success' => false,
                                'error' => 'Failed to create directory: ' . dirname($path),
                                'code' => 'DIRECTORY_CREATE_FAILED',
                            ]);
                        }
                    }
                    
                    // Создаем пустой файл
                    if (!touch($fullFilePath)) {
                        return json_encode([
                            'success' => false,
                            'error' => 'Failed to create file: ' . $path,
                            'code' => 'FILE_CREATE_FAILED',
                        ]);
                    }
                } else {
                    return json_encode([
                        'success' => false,
                        'error' => 'File not found: ' . $path,
                        'code' => 'FILE_NOT_FOUND',
                    ]);
                }
            }

            // Проверяем, что файл доступен для записи
            if (!is_writable($fullFilePath)) {
                return json_encode([
                    'success' => false,
                    'error' => 'File is not writable: ' . $path,
                    'code' => 'FILE_NOT_WRITABLE',
                ]);
            }

            // Читаем содержимое файла
            $content = file_get_contents($fullFilePath);
            if ($content === false) {
                return json_encode([
                    'success' => false,
                    'error' => 'Failed to read file: ' . $path,
                    'code' => 'READ_FAILED',
                ]);
            }

            // Если файл был создан только что, content будет пустым
            $isNewFile = $createIfNotExists && $content === '';

            // Создаем резервную копию файла только если это не новый файл
            $backupPath = null;
            if (!$isNewFile) {
                $backupPath = $fullFilePath . '.backup.' . date('Y-m-d_H-i-s');
                if (!copy($fullFilePath, $backupPath)) {
                    return json_encode([
                        'success' => false,
                        'error' => 'Failed to create backup of file: ' . $path,
                        'code' => 'BACKUP_FAILED',
                    ]);
                }
            }

            // Normalize/prepare regex pattern: accept either fully-delimited regex or raw body
            $finalPattern = $this->normalizePattern($pattern);

            // Выполняем замену
            $originalContent = $content;
            $replacementCount = 0;
            $newContent = preg_replace($finalPattern, $replacement, $content, -1, $replacementCount);

            // Handle regex errors (e.g., invalid delimiter, compilation failure)
            if ($newContent === null) {
                if ($backupPath) {
                    // roll back backup if we created it earlier (we haven't written yet, but stay consistent)
                    unlink($backupPath);
                }

                $regexError = function_exists('preg_last_error_msg') ? preg_last_error_msg() : 'Regex error';
                return json_encode([
                    'success' => false,
                    'error' => 'Failed to apply regex: ' . $regexError,
                    'code' => 'REGEX_ERROR',
                    'data' => [
                        'pattern_provided' => $pattern,
                        'pattern_final' => $finalPattern,
                    ],
                ]);
            }

            // Проверяем, была ли выполнена замена
            if ($originalContent === $newContent) {
                // Удаляем резервную копию, так как изменений не было
                if ($backupPath) {
                    unlink($backupPath);
                }
                
                return json_encode([
                    'success' => true,
                    'message' => 'No matches found for pattern',
                    'data' => [
                        'file_path' => $path,
                        'pattern' => $finalPattern,
                        'replacements_made' => 0,
                        'file_created' => $isNewFile
                    ]
                ]);
            }

            // Записываем новое содержимое в файл
            $result = file_put_contents($fullFilePath, $newContent);

            if ($result === false) {
                // Восстанавливаем файл из резервной копии в случае ошибки
                if ($backupPath) {
                    copy($backupPath, $fullFilePath);
                    unlink($backupPath);
                } else {
                    // Если это был новый файл, удаляем его
                    unlink($fullFilePath);
                }
                
                return json_encode([
                    'success' => false,
                    'error' => 'Failed to write content to file: ' . $path,
                    'code' => 'WRITE_FAILED',
                ]);
            }

            // Количество замен вычислено через preg_replace(..., -1, $replacementCount)
            $replacementsCount = $replacementCount;

            // Удаляем резервную копию после успешной записи
            if ($backupPath) {
                unlink($backupPath);
            }

            return json_encode([
                'success' => true,
                'message' => $isNewFile ? 'File created successfully' : 'File updated successfully',
                'data' => [
                    'file_path' => $path,
                    'pattern' => $finalPattern,
                    'replacement' => $replacement,
                    'replacements_made' => $replacementsCount,
                    'file_created' => $isNewFile,
                    'bytes_written' => $result,
                    'content_length' => strlen((string) $newContent)
                ]
            ]);

        } catch (\Throwable $e) {
            return json_encode([
                'success' => false,
                'error' => 'Failed to replace in file: ' . $e->getMessage(),
                'code' => 'REPLACE_ERROR',
            ]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Replace text in file using a regex pattern or a raw text pattern. If the pattern does not include delimiters, it will be wrapped automatically with ~...~ and modifiers su for multiline Unicode support.',
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
                            'description' => 'Regex pattern to search for. Accepts either a fully-delimited regex (e.g. ~...~su) or a raw pattern body without delimiters, which will be wrapped automatically as ~...~su.',
                        ],
                        'replacement' => [
                            'type' => 'string',
                            'description' => 'Replacement text',
                        ],
                        'create_if_not_exists' => [
                            'type' => 'boolean',
                            'description' => 'Create file if it does not exist. If true, creates the file and any necessary directories.',
                            'default' => false
                        ]
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
