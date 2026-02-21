<?php

namespace Anymodule\Agentmodule\Application\Workflow\Interface;

use Anymodule\Agentmodule\Services\Workflows\Interface\Context;

interface CodeContextInterface extends Context
{
    public function incrementDevRound(): void;

    public function devRound(): int;
}
