<?php

namespace Anymodule\Agentmodule\Application\Actions;

use Anymodule\Agentmodule\Interface\ActionContract;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\DisappearingMessage;

class TipsProcessor implements ActionContract
{
    public function __construct(
        private ActionContract $agent,
        private string         $tips,
    )
    {
    }

    public function execute(Conversation $conversation): \Generator
    {
        if (!$conversation->hasNoUserAnswer()) {
            $conversation->addMessage(new DisappearingMessage($this->tips));
        }

        return $this->agent->execute($conversation);
    }
}