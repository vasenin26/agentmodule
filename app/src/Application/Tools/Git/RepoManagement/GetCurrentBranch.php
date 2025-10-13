<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetCurrentBranch implements ToolInterface
{
    const NAME = 'git-get-current-branch';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url] = $args;

            if (empty($url) || !is_string($url)) {
                return new ToolResult(false, 'URL is required and must be a non-empty string', ['code' => 'INVALID_URL']);
            }

            $repo = $this->repoProvider->getRepo($url);

            $branch = $repo->getCurrentBranchName();

            return new ToolResult(true, 'Git: current branch ok', [
                'branch' => $branch,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to get current branch: ' . $e->getMessage(), ['code' => 'BRANCH_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Get the current branch name of the repository',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
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
