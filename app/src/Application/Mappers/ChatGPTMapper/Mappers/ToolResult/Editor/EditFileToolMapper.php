<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Editor\EditFile;
use Vasenin26\Conversation\Messages\ToolMessage;

class EditFileToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === EditFile::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t edit file';

        $payload = $result['payload'] ?? null;
        $bytesWritten = $payload['bytes_written'] ?? 0;

        // Проверяем, были ли внесены изменения
        if ($bytesWritten > 0) {
            return 'File edited successfully';
        } else {
            return 'No changes made to file';
        }
    }
}
