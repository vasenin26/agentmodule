<?php

namespace Anymodule\Agentmodule\Tools\Git;


use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Utils\Log;

class ReadFile implements ToolInterface
{
    const NAME = 'git-read-file';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    )
    {
    }

    //read file from git repository
    public function execute(array $args): ?string
    {
        list('url' => $url, 'path' => $path) = $args;

        Log::info('Read path: ' . $path);
        Log::info('Read url: ' . $url);

        $repo = $this->repoProvider->getRepo($url);
        $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

        $content = file_get_contents($fullPath);

        if ($content === false) {
            return "File not found: $path";
        }

        return $content;
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
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

    public function getName(): string
    {
        return self::NAME;
    }
}
