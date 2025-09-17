<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers;

use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\PageVersionMessage;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\MessageMapperInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Utils\Log;

class PageVersionMapper implements MessageMapperInterface
{
    public function __construct(
        private PageContextServiceInterface $pageContext
    )
    {
    }

    public function supports(Message $message): bool
    {
        return $message instanceof PageVersionMessage;
    }

    public function map(Message $message): array
    {
        if (!$message instanceof PageVersionMessage) {
            throw new \InvalidArgumentException('Unsupported message type: ' . get_class($message));
        }

        try {
            $version = $this->pageContext->getPageVersion($message->versionId);
            if ($version === null) {
                $content = "Page version not found: {$message->versionId}";
            } else {
                $title = $version->title;
                $body = $version->content;
                $pageId = $version->pageId;
                $versionId = $version->versionId;
                $prevVersionId = $version->previousVersionId;

                $content = "Page title: {$title}\n\n" .
                    "Page content:\n{$body}\n\n" .
                    "Page ID: {$pageId}\n" .
                    "Version ID: {$versionId}\n" .
                    "Previous version ID: " . ($prevVersionId !== null ? $prevVersionId : 'null');
            }

            return [
                'role' => 'user',
                'content' => $content,
            ];
        } catch (\Throwable $e) {
            Log::warning('PageVersionMapper error: ' . $e->getMessage());
            return [
                'role' => 'user',
                'content' => 'Error retrieving page version: ' . $e->getMessage(),
            ];
        }
    }
}


