<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Page\GetHierarchyTree;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetHierarchyTreeToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetHierarchyTree::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get hierarchy tree';

        $payload = $result['payload'] ?? null;
        $tree = $payload['tree'] ?? null;

        if(empty($tree)) {
            return 'No tree data found';
        }

        // Возвращаем только tree, убираем project_id, root_page_id, max_depth, total_pages
        return json_encode($tree);
    }
}
