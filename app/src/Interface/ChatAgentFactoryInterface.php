<?php

namespace Anymodule\Agentmodule\Interface;


interface ChatAgentFactoryInterface
{
    public function createAgent(array $tools): ActionContract;
}