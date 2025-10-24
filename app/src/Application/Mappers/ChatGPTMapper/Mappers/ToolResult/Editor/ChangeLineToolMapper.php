<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Editor\ChangeLine;
use Vasenin26\Conversation\Messages\ToolMessage;

class ChangeLineToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === ChangeLine::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t change line';

        $payload = $result['payload'] ?? null;
        $bytesWritten = $payload['bytes_written'] ?? 0;
        $lineNumber = $payload['line_number'] ?? null;

        // Проверяем, были ли внесены изменения
        if ($bytesWritten > 0) {
            return "Line $lineNumber changed successfully";
        } else {
            return 'No changes made to file';
        }
    }
}
