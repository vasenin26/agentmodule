<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Application\Workflow\ContextRouter;
use Anymodule\Agentmodule\Application\Workflow\Node;
use Anymodule\Agentmodule\Application\Workflow\Nodes\NoOp;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;
use DI\FactoryInterface;

class NodeFactory implements NodeFactoryInterface
{
    public function __construct(
        private FactoryInterface $factory,
    )
    {
    }

    public function createRuledNode(string $nodeKey, $rules): NodeInterface
    {
        $cls = $this->resolveNode($nodeKey);
        $node = $this->factory->make($cls);

        return new Node($nodeKey, new ContextRouter($rules), $node);
    }

    public function createDeadEndNode(string $nodeKey): NodeInterface
    {
        return $this->createRuledNode($nodeKey, null);
    }

    private function resolveNode(string $nodeKey): string
    {
        if (class_exists($nodeKey)) {
            return $nodeKey;
        }

        return NoOp::class;
    }
}