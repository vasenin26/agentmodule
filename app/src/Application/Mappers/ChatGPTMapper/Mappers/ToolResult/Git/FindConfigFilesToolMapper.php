<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\FindConfigFiles;
use Vasenin26\Conversation\Messages\ToolMessage;

class FindConfigFilesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === FindConfigFiles::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t find config files';

        $payload = $result['payload'] ?? null;
        $files = $payload['files'] ?? null;

        if(empty($files)) {
            return 'No config files found';
        }

        // Возвращаем только files, убираем total_files, project_type
        return json_encode($files);
    }
}
