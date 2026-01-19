<?php

namespace Anymodule\Agentmodule\Application\Actions;

use Vasenin26\Conversation\Chat;

final class TestingSession
{
    public function __construct(
        public string $defaultCwd = '/opt/repos',
    ) {
    }

    public ?Chat $testChat = null;

    public ?bool $success = null;

    public string $summary = '';

    public string $errors = '';
}

