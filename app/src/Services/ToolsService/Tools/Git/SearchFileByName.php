<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Git;


use Anymodule\Agentmodule\Interface\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\ToolInterface;

class SearchFileByName implements ToolInterface
{

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }
    public function execute(array $args): ?string
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
            return "Error searching for files.";
        }

        if (empty($output)) {
            return "No files found matching: $path";
        }

        return implode("\n", $output);
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
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
}
