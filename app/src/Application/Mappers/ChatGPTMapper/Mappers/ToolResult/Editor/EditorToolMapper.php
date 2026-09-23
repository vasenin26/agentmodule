<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Editor;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Editor\Insert;
use Anymodule\Agentmodule\Application\Tools\Editor\StrReplace;
use Anymodule\Agentmodule\Application\Tools\Editor\WriteFile;
use Vasenin26\Conversation\Messages\ToolMessage;

/**
 * Инструменты редактора формируют готовый для модели текст (итог + фрагмент с номерами строк)
 * в message как при успехе, так и при ошибке.
 */
class EditorToolMapper implements ToolMapperInterface
{
    private const TOOLS = [
        StrReplace::NAME,
        Insert::NAME,
        WriteFile::NAME,
    ];

    public function supports(ToolMessage $tool): bool
    {
        return in_array($tool->name, self::TOOLS, true);
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        return $result['message'] ?? ($message->success ? 'File updated' : 'Failed to edit file');
    }
}
