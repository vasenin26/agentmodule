<?php

namespace Anymodule\Agentmodule\Application\Tools\Editor\Support;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;

/**
 * Безопасный доступ к файлам репозитория: разрешение путей внутри репозитория,
 * чтение текстовых файлов и атомарная запись.
 */
final class Workspace
{
    public function __construct(
        private GitRepoProviderInterface $repoProvider,
    ) {
    }

    /**
     * @return string абсолютный путь к файлу внутри репозитория
     */
    public function resolve(string $url, string $path): string
    {
        $repoPath = rtrim($this->repoProvider->getRepo($url)->getRepositoryPath(), '/');

        if (!is_dir($repoPath)) {
            throw new EditorException("Repository not found for url: $url", 'REPO_NOT_FOUND');
        }

        $segments = [];
        foreach (explode('/', str_replace('\\', '/', $path)) as $segment) {
            if ($segment === '' || $segment === '.') {
                continue;
            }
            if ($segment === '..') {
                if (array_pop($segments) === null) {
                    throw new EditorException("Path '$path' points outside the repository. Use a path relative to the repository root.", 'PATH_OUTSIDE_REPO');
                }
                continue;
            }
            $segments[] = $segment;
        }

        if ($segments === []) {
            throw new EditorException('Path must point to a file relative to the repository root, e.g. "src/Foo.php".', 'INVALID_PATH');
        }

        if ($segments[0] === '.git') {
            throw new EditorException('Editing files inside .git is not allowed.', 'PATH_FORBIDDEN');
        }

        $fullPath = $repoPath . '/' . implode('/', $segments);

        // Защита от выхода за пределы репозитория через симлинки
        $existing = $fullPath;
        while (!file_exists($existing) && $existing !== $repoPath) {
            $existing = dirname($existing);
        }
        $realRepo = realpath($repoPath);
        $realExisting = realpath($existing);
        if ($realRepo !== false && $realExisting !== false
            && $realExisting !== $realRepo && !str_starts_with($realExisting, $realRepo . '/')) {
            throw new EditorException("Path '$path' resolves outside the repository.", 'PATH_OUTSIDE_REPO');
        }

        return $fullPath;
    }

    public function read(string $fullPath, string $path): TextDocument
    {
        if (is_dir($fullPath)) {
            throw new EditorException("'$path' is a directory, not a file.", 'IS_DIRECTORY');
        }

        if (!file_exists($fullPath)) {
            throw new EditorException("File not found: $path. Check the path with a search tool, or use editor-write-file to create a new file.", 'FILE_NOT_FOUND');
        }

        $raw = @file_get_contents($fullPath);
        if ($raw === false) {
            throw new EditorException("Failed to read file: $path", 'READ_FAILED');
        }

        return TextDocument::fromRaw($raw);
    }

    public function write(string $fullPath, string $path, string $data): void
    {
        $dir = dirname($fullPath);
        if (!is_dir($dir) && !@mkdir($dir, 0755, true) && !is_dir($dir)) {
            throw new EditorException('Failed to create directory: ' . dirname($path), 'DIRECTORY_CREATE_FAILED');
        }

        if (file_exists($fullPath) && !is_writable($fullPath)) {
            throw new EditorException("File is not writable: $path", 'FILE_NOT_WRITABLE');
        }

        $mode = file_exists($fullPath) ? (fileperms($fullPath) & 0777) : 0644;

        // Пишем во временный файл и переименовываем: файл никогда не остаётся записанным наполовину
        $tmp = @tempnam($dir, '.editor-');
        if ($tmp === false || @file_put_contents($tmp, $data) === false) {
            if ($tmp !== false) {
                @unlink($tmp);
            }
            throw new EditorException("Failed to write file: $path", 'WRITE_FAILED');
        }

        @chmod($tmp, $mode);

        if (!@rename($tmp, $fullPath)) {
            @unlink($tmp);
            throw new EditorException("Failed to write file: $path", 'WRITE_FAILED');
        }
    }
}
