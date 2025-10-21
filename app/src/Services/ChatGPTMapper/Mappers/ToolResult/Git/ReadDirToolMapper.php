<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Tools\Git\ReadDir;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class ReadDirToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === ReadDir::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t read dir';

        $payload = $result['payload'] ?? null;
        $files = $payload['files'] ?? null;

        if(empty($files)) {
            return 'Empty dir';
        }

        return json_encode($files);
    }
}