<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Editor\DeleteLines;
use Vasenin26\Conversation\Messages\ToolMessage;

class DeleteLinesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === DeleteLines::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t delete lines';

        $payload = $result['payload'] ?? null;
        $linesDeleted = $payload['lines_deleted'] ?? 0;
        $changesMade = $payload['changes_made'] ?? false;

        // Проверяем, были ли внесены изменения
        if ($changesMade && $linesDeleted > 0) {
            return "Successfully deleted $linesDeleted line(s)";
        } else {
            return 'No changes made to file';
        }
    }
}
