<?php

namespace Anymodule\Agentmodule\Application\Actions;

use Anymodule\Agentmodule\Interface\ActionContract;
use Vasenin26\Conversation\Interface\Conversation;

readonly class ProcessChat implements ActionContract
{
    public function __construct(
        private ActionContract $agent
    )
    {
    }

    public function execute(Conversation $conversation): \Generator
    {
        return $this->agent->execute($conversation);
    }
}