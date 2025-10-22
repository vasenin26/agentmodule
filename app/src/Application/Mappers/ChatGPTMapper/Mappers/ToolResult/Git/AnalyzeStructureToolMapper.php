<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\AnalyzeStructure;
use Vasenin26\Conversation\Messages\ToolMessage;

class AnalyzeStructureToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === AnalyzeStructure::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t analyze structure';

        $payload = $result['payload'] ?? null;
        
        // Возвращаем только важные поля: project_type, main_directories, entry_points, config_files
        $filteredData = [
            'project_type' => $payload['project_type'] ?? null,
            'main_directories' => $payload['main_directories'] ?? null,
            'entry_points' => $payload['entry_points'] ?? null,
            'config_files' => $payload['config_files'] ?? null,
        ];

        return json_encode($filteredData);
    }
}
