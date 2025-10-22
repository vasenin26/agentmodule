<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\SearchFileByName;
use Vasenin26\Conversation\Messages\ToolMessage;

class SearchFileByNameToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === SearchFileByName::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t search files';

        $payload = $result['payload'] ?? null;
        $files = $payload['files'] ?? null;

        if(empty($files)) {
            return 'No files found';
        }

        return json_encode($files);
    }
}
