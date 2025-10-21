<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult;

use Anymodule\Agentmodule\Application\Tools\CurrentTime;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class CurrentTimeToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === CurrentTime::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get current time';

        $payload = $result['payload'] ?? null;
        $datetime = $payload['datetime'] ?? null;

        if(empty($datetime)) {
            return 'No datetime found';
        }

        return $datetime;
    }
}
