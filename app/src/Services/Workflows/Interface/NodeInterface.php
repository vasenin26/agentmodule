<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;

interface NodeInterface
{
    public function process(Context $ctx): StepResult;

    public function defineCurrentNode(Context $ctx): string;

    public function getKey(): string;
}