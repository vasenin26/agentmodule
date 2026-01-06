<?php

namespace Anymodule\Agentmodule\Application\Workflow\Interface;

interface PlanableContextInterface
{
    public function setPlane(array $tasks): void;
}