<?php

namespace Anymodule\Agentmodule\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Entity\ToolResult;

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
            ['url' => $url, 'remote' => $remote, 'branch' => $branch] = $args + ['remote' => 'origin', 'branch' => null];

            if (empty($url) || !is_string($url)) {
                return new ToolResult(false, 'URL is required and must be a non-empty string', ['code' => 'INVALID_URL']);
            }

            $repo = $this->repoProvider->getRepo($url);

            // Если указана ветка, переключаемся на неё
            if ($branch) {
                $currentBranch = $repo->getCurrentBranchName();
                if ($currentBranch !== $branch) {
                    $repo->checkout($branch);
                }
            }

            // Выполняем pull
            $repo->pull($remote);

            return new ToolResult(true, 'Git: pull ok', [
                'remote' => $remote,
                'branch' => $branch ?: $repo->getCurrentBranchName(),
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
                        ],
                        'branch' => [
                            'type' => 'string',
                            'description' => 'Branch name to pull (optional, uses current branch if not specified)',
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
