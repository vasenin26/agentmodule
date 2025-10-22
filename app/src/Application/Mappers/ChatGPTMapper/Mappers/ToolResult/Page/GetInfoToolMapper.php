<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Page\GetInfo;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetInfoToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetInfo::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get page info';

        $payload = $result['payload'] ?? null;
        $page = $payload['page'] ?? null;

        if(empty($page)) {
            return 'No page data found';
        }

        // Возвращаем только page с id, title, content, files
        return json_encode($page);
    }
}
