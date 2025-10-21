<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Tools\Git\SearchPattern;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class SearchPatternToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === SearchPattern::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t search pattern';

        $payload = $result['payload'] ?? null;
        $matches = $payload['matches'] ?? null;

        if(empty($matches)) {
            return 'No matches found';
        }

        // Возвращаем только matches, убираем pattern, mode, modifiers, file_extensions, max_results, total_matches
        return json_encode($matches);
    }
}
