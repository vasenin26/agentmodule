<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;

interface ActionContract
{
    /**
     * @param Chat $conversation
     * @return \Generator<ProcessingResult>
     */
    public function execute(Conversation $conversation): \Generator;
}