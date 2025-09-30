<?php

namespace Anymodule\Agentmodule\Tools\Git;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

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
            return new ToolResult(false, 'Error searching for files', ['code' => 'SEARCH_ERROR']);
        }

        if (empty($output)) {
            return null; // ничего не найдено
        }

        return new ToolResult(true, 'Search: files found', ['files' => array_values($output)]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Search files by name in repository.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository url',
                        ],
                        'needle' => [
                            'type' => 'string',
                            'description' => 'File name',
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
