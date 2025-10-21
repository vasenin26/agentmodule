<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Editor;

use Anymodule\Agentmodule\Application\Tools\Editor\InsertOrReplace;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class InsertOrReplaceToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === InsertOrReplace::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t insert or replace';

        $payload = $result['payload'] ?? null;
        $operationType = $payload['operation_type'] ?? null;
        $bytesWritten = $payload['bytes_written'] ?? 0;

        // Проверяем, были ли внесены изменения
        if ($bytesWritten > 0) {
            return 'File updated successfully (' . $operationType . ')';
        } else {
            return 'No changes made to file';
        }
    }
}
