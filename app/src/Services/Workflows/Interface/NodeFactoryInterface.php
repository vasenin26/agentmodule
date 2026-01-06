<?php

namespace Anymodule\Agentmodule\Services\Workflows\Interface;

interface NodeFactoryInterface
{

    public function createRuledNode(string $nodeKey, $rules): NodeInterface;

    public function createDeadEndNode(string $nodeKey): NodeInterface;
}