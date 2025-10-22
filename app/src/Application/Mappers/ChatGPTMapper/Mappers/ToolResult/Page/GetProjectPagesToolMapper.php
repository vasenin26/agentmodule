<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Page\GetProjectPages;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetProjectPagesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetProjectPages::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get project pages';

        $payload = $result['payload'] ?? null;
        $pages = $payload['pages'] ?? null;

        if(empty($pages)) {
            return 'No pages found';
        }

        // Возвращаем только pages, убираем total_pages, root_pages_count, page_tree, statistics
        return json_encode($pages);
    }
}
