<?php

namespace Anymodule\Agentmodule\Actions;

use Anymodule\Agentmodule\Interface\ActionContract;
use Vasenin26\Conversation\Interface\Conversation;

class ProcessChat implements ActionContract
{
    public function __construct(
        private ActionContract $agent
    )
    {
    }

    public static function getName(): string
    {
        return 'process-chat';
    }

    public function execute(Conversation $conversation): \Generator
    {
        return $this->agent->execute($conversation);
    }
}