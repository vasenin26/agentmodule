<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\ProcessingResult;

interface ChatSummaryGeneratorInterface
{

    /**
     * @param \Vasenin26\Conversation\Interface\Conversation $conversation
     * @return \Generator<ProcessingResult>
     */
    public function generate(\Vasenin26\Conversation\Interface\Conversation $conversation): \Generator;
}