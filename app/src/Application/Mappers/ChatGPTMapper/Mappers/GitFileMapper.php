<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\MessageMapperInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\GitFileMessage;

class GitFileMapper implements MessageMapperInterface
{
    private const MAX_FILE_LENGTH = 5000; // max chars to include

    public function __construct(
        private GitRepoProviderInterface $repositoryProvider,
    ) {}

    public function supports(Message $message): bool
    {
        return $message instanceof GitFileMessage;
    }

    public function map(Message $message): array
    {
        if (!$message instanceof GitFileMessage) {
            throw new \InvalidArgumentException('Unsupported message type');
        }

        $content = "📁 Repository: {$message->url}\n";
        $content .= "📄 File: {$message->path}\n";

        // structured description
        if ($message->description) {
            if (is_array($message->description)) {
                $desc = json_encode($message->description, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
                $content .= "📝 Description:\n{$desc}\n";
            } else {
                $content .= "📝 Description: {$message->description}\n";
            }
        }

        // include file content if it's not too large
        $fileContent = $this->getFileContent($message->url, $message->path);

        if (mb_strlen($fileContent) > 0 && mb_strlen($fileContent) <= self::MAX_FILE_LENGTH) {
            $content .= "📄 File content:\n```\n{$fileContent}\n```\n";
        } elseif (mb_strlen($fileContent) > self::MAX_FILE_LENGTH) {
            $content .= "⚠️ File too large to include. Use the tool `git-read-file` to retrieve the content.\n";
        } else {
            $content .= "⚠️ Unable to read file content.\n";
        }

        return [
            'role' => 'system',
            'content' => $content
        ];
    }

    private function getFileContent(string $repoUrl, string $filePath): string
    {
        try {
            $repo = $this->repositoryProvider->getRepo($repoUrl);
            $fullPath = $repo->getRepositoryPath() . '/' . trim($filePath, '/');
            $content = @file_get_contents($fullPath);
            return $content !== false ? $content : '';
        } catch (\Throwable $e) {
            Log::warning('GitFileMapper error: ' . $e->getMessage());
            return '';
        }
    }
}
