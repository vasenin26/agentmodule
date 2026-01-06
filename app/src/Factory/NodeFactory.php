<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\Workflow\Node;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;

class NodeFactory implements NodeFactoryInterface
{

    public function createRuledNode(string $nodeKey, $rules): NodeInterface
    {
        $cls = $this->resolveNode($nodeKey);
        $node = new $cls();

        return new Node($nodeKey, new ContextRoute($rules), $node);
    }

    public function createDeadEndNode(string $nodeKey): NodeInterface
    {
        return $this->createRuledNode($nodeKey, null);
    }

    private function resolveNode(string $nodeKey): string
    {
        return $nodeKey;
    }
}