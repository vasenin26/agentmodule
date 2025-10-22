<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Page;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Page\GetAttachedFiles;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetAttachedFilesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetAttachedFiles::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get attached files';

        $payload = $result['payload'] ?? null;
        $files = $payload['files'] ?? null;

        if(empty($files)) {
            return 'No files found';
        }

        // Возвращаем только files, убираем page_id, total_files
        return json_encode($files);
    }
}
