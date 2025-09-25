<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Url\UrlParserInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\GitFileMessage;

class GitFileMapper implements MessageMapperInterface
{
    private GitRepoProviderInterface $repositoryProvider;
    private UrlParserInterface $urlParser;

    public function __construct(
        GitRepoProviderInterface $repositoryProvider,
        UrlParserInterface       $urlParser
    )
    {
        $this->repositoryProvider = $repositoryProvider;
        $this->urlParser = $urlParser;
    }

    public function supports(Message $message): bool
    {
        return $message instanceof GitFileMessage;
    }

    public function map(Message $message): array
    {
        if ($message instanceof GitFileMessage) {
            $fileContent = $this->getFileContent($message->url, $message->path);

            $content = "Git repo: {$message->url}\n";
            $content .= "File path: {$message->path}\n";
            if ($message->description) {
                $content .= "Description: {$message->description}\n";
            }
            $content .= "START FILE CONTENT \n```\n";
            $content .= $fileContent;
            $content .= "```\n END FILE CONTENT \n";

            return [
                'role' => 'user',
                'content' => $content
            ];
        }

        throw new \Exception("Unsupported message type");
    }

    private function getFileContent(string $repoUrl, string $filePath): string
    {
        try {
            Log::info('GitFileMapper - Read path: ' . $filePath);
            Log::info('GitFileMapper - Read url: ' . $repoUrl);

            $repo = $this->repositoryProvider->getRepo($repoUrl);

            $fullPath = $repo->getRepositoryPath() . '/' . trim($filePath, '/');

            $content = file_get_contents($fullPath);

            if ($content === false) {
                return "File not found";
            }

            return $content;
        } catch (\Exception $e) {
            Log::warning('GitFileMapper error: ' . $e->getMessage());
            return "Error retrieving file content: " . $e->getMessage();
        }
    }

}
