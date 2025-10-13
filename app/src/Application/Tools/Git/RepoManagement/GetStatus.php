<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class GetStatus implements ToolInterface
{
    const NAME = 'git-get-status';

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

            // Выполняем git status --porcelain
            $result = $repo->run(['status', '--porcelain']);
            $outputArray = $result->getOutput();
            $lines = is_array($outputArray) ? $outputArray : explode("\n", trim($outputArray));

            $modified = [];
            $staged = [];
            $untracked = [];
            $deleted = [];

            foreach ($lines as $line) {
                if (empty($line)) continue;

                $status = substr($line, 0, 2);
                $file = trim(substr($line, 3));

                // Анализируем статус файла
                if (strpos($status, 'M') !== false) {
                    if ($status[0] === 'M') {
                        $staged[] = $file;
                    } else {
                        $modified[] = $file;
                    }
                } elseif (strpos($status, 'A') !== false) {
                    $staged[] = $file;
                } elseif (strpos($status, 'D') !== false) {
                    if ($status[0] === 'D') {
                        $staged[] = $file;
                    } else {
                        $deleted[] = $file;
                    }
                } elseif (strpos($status, '?') !== false) {
                    $untracked[] = $file;
                } elseif (strpos($status, 'R') !== false) {
                    $staged[] = $file;
                }
            }

            return new ToolResult(true, 'Git: status ok', [
                'modified' => $modified,
                'staged' => $staged,
                'untracked' => $untracked,
                'deleted' => $deleted,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to get repository status: ' . $e->getMessage(), ['code' => 'STATUS_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Get repository status showing modified, staged, untracked and deleted files',
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
