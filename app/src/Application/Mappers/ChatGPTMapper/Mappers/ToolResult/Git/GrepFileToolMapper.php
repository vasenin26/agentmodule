<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\GrepFile;
use Vasenin26\Conversation\Messages\ToolMessage;

class GrepFileToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GrepFile::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t grep file';

        $payload = $result['payload'] ?? null;
        $matches = $payload['matches'] ?? null;

        if(empty($matches)) {
            return 'No matches found';
        }

        // Возвращаем только matches, убираем path, pattern, total_lines, matches_count
        return json_encode($matches);
    }
}
