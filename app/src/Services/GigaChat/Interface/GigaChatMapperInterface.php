<?php

namespace Anymodule\Agentmodule\Services\GigaChat\Interface;

use Anymodule\Agentmodule\Services\GigaChat\DTO\GigaResult;

interface GigaChatMapperInterface
{

    public function mapChat(\Vasenin26\Conversation\Chat $chat): array;

    public function prepareAssistantMessage($result): GigaResult;
}