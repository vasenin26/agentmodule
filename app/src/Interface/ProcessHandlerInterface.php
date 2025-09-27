<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Entity\ProcessingResult;

interface ProcessHandlerInterface
{
    public function handle(ProcessingResult $result): void;
}