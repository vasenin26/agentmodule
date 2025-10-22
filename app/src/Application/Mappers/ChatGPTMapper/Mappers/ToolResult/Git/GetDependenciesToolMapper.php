<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\GetDependencies;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetDependenciesToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetDependencies::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get dependencies';

        $payload = $result['payload'] ?? null;
        $dependencies = $payload['dependencies'] ?? null;

        if(empty($dependencies)) {
            return 'No dependencies found';
        }

        // Возвращаем только dependencies, убираем project_type, total_dependencies
        return json_encode($dependencies);
    }
}
