<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\ChatFactoryInterface;
use Anymodule\Agentmodule\Interface\GPTProcessorInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\LLMGenerator\LMStudioClient;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;

class ChatFactory implements ChatFactoryInterface
{

    public function createChat(ToolsService $toolsService): GPTProcessorInterface
    {
        $apiKey = getenv('OPENAI_API_KEY');
        return new LMStudioClient(
            $apiKey,
            $toolsService,
            new ChatMapper()
        );
    }
}