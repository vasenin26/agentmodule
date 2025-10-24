<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Editor\InsertLines;
use Vasenin26\Conversation\Messages\ToolMessage;

class InsertLinesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === InsertLines::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t insert lines';

        $payload = $result['payload'] ?? null;
        $linesInserted = $payload['lines_inserted'] ?? 0;
        $insertMode = $payload['insert_mode'] ?? 'after';
        $targetLine = $payload['target_line'] ?? null;
        $changesMade = $payload['changes_made'] ?? false;

        // Проверяем, были ли внесены изменения
        if ($changesMade && $linesInserted > 0) {
            return "Successfully inserted $linesInserted line(s) $insertMode line $targetLine";
        } else {
            return 'No changes made to file';
        }
    }
}
