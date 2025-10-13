<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ResetHard implements ToolInterface
{
    const NAME = 'git-reset-hard';

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

            // Выполняем reset --hard
            $result = $repo->run(['reset', '--hard']);
            if (!$result->isOk()) {
                throw new \Exception('Reset failed: ' . $result->getErrorOutput());
            }

            return new ToolResult(true, 'Git: reset --hard ok');

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to reset repository: ' . $e->getMessage(), ['code' => 'RESET_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Reset repository to last commit, discarding all uncommitted changes (git reset --hard)',
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
