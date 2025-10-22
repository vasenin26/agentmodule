<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\AnalyzeClasses;
use Vasenin26\Conversation\Messages\ToolMessage;

class AnalyzeClassesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === AnalyzeClasses::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t analyze classes';

        $payload = $result['payload'] ?? null;
        
        // Возвращаем только важные поля: classes, interfaces, traits, namespaces
        $filteredData = [
            'classes' => $payload['classes'] ?? null,
            'interfaces' => $payload['interfaces'] ?? null,
            'traits' => $payload['traits'] ?? null,
            'namespaces' => $payload['namespaces'] ?? null,
        ];

        return json_encode($filteredData);
    }
}
