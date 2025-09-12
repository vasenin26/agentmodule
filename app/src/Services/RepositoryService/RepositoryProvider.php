<?php

namespace Anymodule\Agentmodule\Services\RepositoryService;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use CzProject\GitPhp\Git;
use CzProject\GitPhp\GitRepository;

class RepositoryProvider implements GitRepoProviderInterface
{
    private array $repos = [];

    public function __construct(
        private string  $reposFolder = 'default',
        private ?string $branch,
    )
    {
    }

    public function getRepo(string $url): GitRepository
    {
        // Преобразуем HTTPS ссылку в SSH
        if (str_starts_with($url, 'https://')) {
            $url = $this->convertHttpsToSsh($url);
        }

        $fullPath = $this->defineRepositoryFolder($url);

        if (array_key_exists($fullPath, $this->repos)) {
            return $this->repos[$fullPath];
        }

        $git = new Git();

        if (is_dir($fullPath)) {
            $repo = $git->open($fullPath);
        } else {
            $repo = $git->cloneRepository($url, $fullPath);
        }

        if ($this->branch) {
            $repo->checkout($this->branch);
        }

        return $this->repos[$fullPath] = $repo;
    }

    /**
     * @return GitRepository[]
     */
    public function getProvidedRepositories(): array
    {
        return $this->repos;
    }

    /**
     * Преобразует HTTPS ссылку в SSH формат
     *
     * @param string $httpsUrl HTTPS ссылка вида https://domain.com/username/repo/blob/branch/file
     * @return string SSH ссылка вида git@domain.com:username/repo.git
     */
    public function convertHttpsToSsh(string $httpsUrl): string
    {
        // Проверяем, что это HTTPS ссылка
        if (!str_starts_with($httpsUrl, 'https://')) {
            return $httpsUrl; // Возвращаем исходную ссылку, если это не HTTPS
        }

        // Парсим URL
        $parsedUrl = parse_url($httpsUrl);
        if (!$parsedUrl || !isset($parsedUrl['host']) || !isset($parsedUrl['path'])) {
            return $httpsUrl;
        }

        $domain = $parsedUrl['host'];

        // Извлекаем путь: /username/repo/blob/branch/file
        $path = trim($parsedUrl['path'], '/');

        // Разбиваем путь на части
        $pathParts = explode('/', $path);

        // Проверяем, что у нас есть минимум username и repo
        if (count($pathParts) < 2) {
            return $httpsUrl;
        }

        $username = $pathParts[0];
        $repo = $pathParts[1];

        // Формируем SSH ссылку
        return "git@{$domain}:{$username}/{$repo}.git";
    }

    private function defineRepositoryFolder(string $url): string
    {
        $domain = '';
        $path = '';

        // Парсинг URL в зависимости от формата
        if (str_starts_with($url, 'https://')) {
            // HTTPS формат
            $parsed_url = parse_url($url);
            $domain = $parsed_url['host'];
            $path = trim($parsed_url['path'], '/');
        }

        // SSH формат: git@github.com:username/repository.git
        $parts = explode(':', $url);
        if (count($parts) === 2) {
            $domainPart = $parts[0]; // git@github.com
            $pathPart = $parts[1];   // username/repository.git

            // Извлекаем домен из git@domain
            $domainParts = explode('@', $domainPart);
            if (count($domainParts) === 2) {
                $domain = $domainParts[1]; // github.com
            }

            $path = $pathPart;
        }

        // Убираем .git из пути если есть
        $path = preg_replace('/\.git$/', '', $path);

        return '/home/local/repos/' . $this->reposFolder . '/'. $domain . '/' . $path;
    }
}
