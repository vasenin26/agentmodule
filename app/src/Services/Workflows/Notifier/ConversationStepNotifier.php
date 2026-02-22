<?php

namespace Anymodule\Agentmodule\Services\Workflows\Notifier;

use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\StepNotifierInterface;
use Vasenin26\Conversation\Messages\InfoMessage;

class ConversationStepNotifier implements StepNotifierInterface
{
    public function notifyStepStart(Context $ctx, string $step): void
    {
        $ctx->getContextConversation()->conversation->addMessage(new InfoMessage('Current step: ' . $step));
    }
}
