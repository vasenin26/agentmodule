<?php

namespace Anymodule\Agentmodule\Utils;

use Anymodule\Agentmodule\Interface\Url\UrlParserInterface;

class ExtractRepoUrl implements UrlParserInterface
{
    public function extractRepoUrl(string $url): string
    {
        if (preg_match('/^https:\/\/([^\/]+)\/([^\/]+)\/([^\/]+)/', $url, $matches)) {
            $domain = $matches[1];
            $owner = $matches[2];
            $repo = $matches[3];
            
            if (str_ends_with($repo, '.git')) {
                return "https://{$domain}/{$owner}/{$repo}";
            }
            
            return "https://{$domain}/{$owner}/{$repo}.git";
        }
        
        return $url;
    }

    public function extractFilePath(string $url): string
    {
        if (preg_match('/\/blob\/[^\/]+\/(.+)$/', $url, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/\/-\/blob\/[^\/]+\/(.+)$/', $url, $matches)) {
            return $matches[1];
        }
        
        return basename(parse_url($url, PHP_URL_PATH));
    }

    public function extractOwnerAndRepo(string $url): ?array
    {
        if (preg_match('/^https:\/\/([^\/]+)\/([^\/]+)\/([^\/]+)/', $url, $matches)) {
            return [
                'domain' => $matches[1],
                'owner' => $matches[2],
                'repo' => $matches[3]
            ];
        }
        
        return null;
    }

    public function extractBranch(string $url): ?string
    {
        if (preg_match('/\/blob\/([^\/]+)\//', $url, $matches)) {
            return $matches[1];
        }
        
        if (preg_match('/\/-\/blob\/([^\/]+)\//', $url, $matches)) {
            return $matches[1];
        }
        
        return null;
    }

    public function isGitHubUrl(string $url): bool
    {
        return str_contains($url, 'github.com');
    }

    public function convertHttpsToSsh(string $httpsUrl): string
    {
        if (!str_starts_with($httpsUrl, 'https://')) {
            return $httpsUrl;
        }

        $parsedUrl = parse_url($httpsUrl);
        if (!$parsedUrl || !isset($parsedUrl['host']) || !isset($parsedUrl['path'])) {
            return $httpsUrl;
        }

        $domain = $parsedUrl['host'];
        $path = trim($parsedUrl['path'], '/');
        $pathParts = explode('/', $path);

        if (count($pathParts) < 2) {
            return $httpsUrl;
        }

        $username = $pathParts[0];
        $repo = $pathParts[1];

        if (!str_ends_with($repo, '.git')) {
            $repo .= '.git';
        }

        return "git@{$domain}:{$username}/{$repo}";
    }
}
