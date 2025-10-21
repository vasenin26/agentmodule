<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Mappers\ToolResult\Page;

use Anymodule\Agentmodule\Application\Tools\Page\GetActualizationInfo;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolMapperInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetActualizationInfoToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetActualizationInfo::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get actualization info';

        $payload = $result['payload'] ?? null;
        $actualizations = $payload['actualizations'] ?? null;

        if(empty($actualizations)) {
            return 'No actualizations found';
        }

        // Возвращаем только actualizations, убираем page_id, total_actualizations
        return json_encode($actualizations);
    }
}
