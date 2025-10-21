<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Tools\Git\ReadFileLines;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class ReadFileLinesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === ReadFileLines::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t read file lines';

        $payload = $result['payload'] ?? null;
        $content = $payload['content'] ?? null;

        if(empty($content)) {
            return 'No content found';
        }

        return $content;
    }
}
