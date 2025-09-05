<?php

namespace Anymodule\Agentmodule\Services\ToolsService\Tools\Git;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ReadFile implements ToolInterface
{

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }

    //read file from git repository
    public function execute(array $args): ?string
    {
        list('url' => $url, 'path' => $path) = $args;

        $repo = $this->repoProvider->getRepo($url);
        $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

        $content = file_get_contents($fullPath);

        if ($content === false) {
            return "File not found: $path";
        }

        return $content;
    }

    public function getProps($name): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $name,
                'description' => 'Read file from repository',
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
                        ]
                    ],
                    'required' => ['url', 'path'],
                ]
            ]
        ];
    }
}
