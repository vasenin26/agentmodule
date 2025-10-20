<?php

namespace Anymodule\Agentmodule\Interface;

use Vasenin26\Conversation\Interface\Conversation;

interface ActionRunnerInterface
{
    public function run(Conversation $conversation): void;
}