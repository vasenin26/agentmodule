<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Vasenin26\Conversation\Chat;

interface ActionContract
{
    /**
     * @param Chat $instructions
     * @return \Generator<ProcessingResult>
     */
    public function execute(Chat $instructions): \Generator;
}