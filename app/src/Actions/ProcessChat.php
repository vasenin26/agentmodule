<?php

namespace Anymodule\Agentmodule\Actions;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;
use Vasenin26\Conversation\Chat;

class ProcessChat implements ActionContract
{
    public function __construct(
        private ToolsService              $toolsService,
        private ChatAgentFactoryInterface $chatAgentFactory,
    )
    {
    }

    public static function getName(): string
    {
        return 'process-chat';
    }

    public function execute(Chat $instructions): \Generator
    {
        $agent = $this->chatAgentFactory->createAgent($this->toolsService);
        return $agent->execute($instructions);
    }
}