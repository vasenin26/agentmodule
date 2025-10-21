<?php

namespace Anymodule\Agentmodule\Application\Tools\Git;


use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class SearchFileByName implements ToolInterface
{
    const NAME = 'git-search-file-by-name';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }
    public function execute(array $args): ?ToolResult
    {
        list('url' => $url, 'needle' => $path) = $args;

        $repo = $this->repoProvider->getRepo($url);
        $repoPath = $repo->getRepositoryPath();

        $needle = escapeshellarg($path);

        $command = "find " . escapeshellarg($repoPath) . " -type f -iname $needle";

        $output = [];
        $returnVar = 0;
        exec($command, $output, $returnVar);

        if ($returnVar !== 0) {
            return new ToolResult(false, "Error searching for files $needle", []);
        }

        if (empty($output)) {
            return new ToolResult(false, "Not found for $needle", []);
        }

        // Преобразуем абсолютные пути в относительные от корня репозитория
        $relativePaths = [];
        foreach ($output as $absolutePath) {
            $relativePath = str_replace($repoPath . '/', '', $absolutePath);
            $relativePaths[] = $relativePath;
        }

        return new ToolResult(true, 'Search: files found', ['files' => array_values($relativePaths)]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Search files by full name in repository.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository url',
                        ],
                        'needle' => [
                            'type' => 'string',
                            'description' => 'File name with extension',
                        ]
                    ],
                    'required' => ['url', 'needle'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
