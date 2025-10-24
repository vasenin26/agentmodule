<?php

namespace Anymodule\Agentmodule\Application\Tools\Git;


use Anymodule\Agentmodule\Entity\ToolResult;
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
    public function execute(array $args): ?ToolResult
    {
        list('url' => $url, 'path' => $path, 'line' => $line) = $args + ['line' => false];

        Log::info('Read path: ' . $path);
        Log::info('Read url: ' . $url);

        $repo = $this->repoProvider->getRepo($url);
        $fullPath = $repo->getRepositoryPath() . '/' . trim($path, '/');

        $content = @file_get_contents($fullPath);

        if ($content === false) {
            return new ToolResult(false, 'File not found: ' . $path, ['code' => 'FILE_NOT_FOUND']);
        }

        if($line) {
            $lines = explode("\n", $content);
            $result = [];

            foreach ($lines as $idx => $line) {
                $result[] = 'L' . ($idx+1) . ':' . $line;
            }

            $content = join("\n", $result);
        }

        return new ToolResult(true, 'Git: read file ok', [
            'path' => $path,
            'content' => $content,
        ]);
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
                        ],
                        'line' => [
                            'type' => 'boolean',
                            'description' => 'If true, prepend each line with its 1-based line number.',
                        ],
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
