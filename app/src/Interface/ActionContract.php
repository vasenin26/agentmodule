<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Vasenin26\Conversation\Chat;

interface ActionContract
{
    public function execute(Chat $instructions): ProcessingResult;
}