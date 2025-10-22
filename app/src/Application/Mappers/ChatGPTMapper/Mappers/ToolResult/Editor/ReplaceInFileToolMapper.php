<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Editor\ReplaceInFile;
use Vasenin26\Conversation\Messages\ToolMessage;

class ReplaceInFileToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === ReplaceInFile::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t replace in file';

        $payload = $result['payload'] ?? null;
        $replacementsMade = $payload['replacements_made'] ?? 0;

        // Проверяем, были ли внесены изменения
        if ($replacementsMade > 0) {
            return 'File updated successfully (' . $replacementsMade . ' replacements made)';
        } else {
            return 'No changes made - pattern not found';
        }
    }
}
