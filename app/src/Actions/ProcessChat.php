<?php

namespace Anymodule\Agentmodule\Actions;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Vasenin26\Conversation\Chat;

class ProcessChat implements ActionContract
{
    public static function getName(): string
    {
        return 'process-chat';
    }

    public function execute(Chat $instructions): ProcessingResult
    {
        return new ProcessingResult(
            true,
            '',
            new Chat(),
            0,
            0,
            0,
        );
    }
}