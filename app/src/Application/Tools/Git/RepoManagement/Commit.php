<?php

namespace Anymodule\Agentmodule\Application\Tools\Git\RepoManagement;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\FileModifyingToolInterface;

class Commit implements FileModifyingToolInterface
{
    const NAME = 'git-commit';

    public function __construct(
        private GitRepoProviderInterface $repoProvider
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        try {
            ['url' => $url, 'message' => $message, 'authorName' => $authorName, 'authorEmail' => $authorEmail] = $args + ['authorName' => null, 'authorEmail' => null];

            if (empty($url) || !is_string($url)) {
                return new ToolResult(false, 'URL is required and must be a non-empty string', ['code' => 'INVALID_URL']);
            }

            if (empty($message) || !is_string($message)) {
                return new ToolResult(false, 'Message is required and must be a non-empty string', ['code' => 'INVALID_MESSAGE']);
            }

            $repo = $this->repoProvider->getRepo($url);

            // Если указаны данные автора, используем их
            if ($authorName && $authorEmail) {
                $result = $repo->run(['-c', 'user.name=' . $authorName, '-c', 'user.email=' . $authorEmail, 'commit', '-m', $message]);
                if (!$result->isOk()) {
                    throw new \Exception('Commit failed: ' . $result->getErrorOutput());
                }
            } else {
                $repo->commit($message);
            }

            return new ToolResult(true, 'Git: commit ok', [
                'commit_message' => $message,
                'author_name' => $authorName,
                'author_email' => $authorEmail,
            ]);

        } catch (\Throwable $e) {
            return new ToolResult(false, 'Failed to create commit: ' . $e->getMessage(), ['code' => 'COMMIT_ERROR', 'exception' => get_class($e)]);
        }
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Create a new commit with staged changes',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'url' => [
                            'type' => 'string',
                            'description' => 'Git repository URL',
                        ],
                        'message' => [
                            'type' => 'string',
                            'description' => 'Commit message',
                        ],
                        'authorName' => [
                            'type' => 'string',
                            'description' => 'Author name for the commit (optional)',
                        ],
                        'authorEmail' => [
                            'type' => 'string',
                            'description' => 'Author email for the commit (optional)',
                        ]
                    ],
                    'required' => ['url', 'message'],
                ]
            ]
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
