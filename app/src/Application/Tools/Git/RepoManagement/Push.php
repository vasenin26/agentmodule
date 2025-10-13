<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class Push implements ToolInterface
{
    const NAME = 'git-push';

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

            // Если ветка не указана, используем текущую
            if (!$branch) {
                $branch = $repo->getCurrentBranchName();
            }

            // Выполняем push
            $repo->push($remote, [$branch, '--set-upstream']);

            return new ToolResult(true, 'Git: push ok', [
                'remote' => $remote,
                'branch' => $branch,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to push changes: ' . $e->getMessage(), ['code' => 'PUSH_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Push committed changes to remote repository',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
                        ],
                        'remote' => [
                            'type' => 'string',
                            'description' => 'Remote name to push to',
                            'default' => 'origin'
                        ],
                        'branch' => [
                            'type' => 'string',
                            'description' => 'Branch name to push (optional, uses current branch if not specified)',
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
