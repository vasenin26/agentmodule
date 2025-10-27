<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ProcessingResult;

interface ContextActionContract
{
    /**
     * @param ContextConversation $conversation
     * @return \Generator<ProcessingResult>
     */
    public function execute(ContextConversation $conversation): \Generator;
}