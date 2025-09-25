<?php

namespace Anymodule\Agentmodule\Actions;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Vasenin26\Conversation\Chat;

class SearchFiles implements ActionContract
{
    public static function getName(): string
    {
        return 'search-relevant-files';
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