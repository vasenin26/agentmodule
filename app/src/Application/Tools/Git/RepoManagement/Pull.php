<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class Pull implements ToolInterface
{
    const NAME = 'git-pull';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'remote' => $remote] = $args + ['remote' => 'origin'];

            if (empty($url) || !is_string($url)) {
                return new ToolResult(false, 'URL is required and must be a non-empty string', ['code' => 'INVALID_URL']);
            }

            $repo = $this->repoProvider->getRepo($url);

            // Выполняем pull
            $repo->pull($remote);

            return new ToolResult(true, 'Git: pull ok', [
                'url' => $url,
                'remote' => $remote,
                'branch' => $repo->getCurrentBranchName(),
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to pull repository: ' . $e->getMessage(), ['code' => 'PULL_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Pull latest changes from remote repository',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
                        ],
                        'remote' => [
                            'type' => 'string',
                            'description' => 'Remote name to pull from',
                            'default' => 'origin'
                        ]
                    ],
                    'required' => ['url'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
