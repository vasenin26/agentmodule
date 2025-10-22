<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Page\FindRelatedPages;
use Vasenin26\Conversation\Messages\ToolMessage;

class FindRelatedPagesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === FindRelatedPages::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t find related pages';

        $payload = $result['payload'] ?? null;
        $pages = $payload['pages'] ?? null;

        if(empty($pages)) {
            return 'No related pages found';
        }

        // Возвращаем только pages, убираем page_id, total_pages, search_criteria
        return json_encode($pages);
    }
}
