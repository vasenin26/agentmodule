<?php

namespace Anymodule\Agentmodule\Application\Workflow;

use Anymodule\Agentmodule\Application\Workflow\ContextRouter;
use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\NodeInterface;

final readonly class Node implements NodeInterface
{
    public function __construct(
        private string $key,
        private ContextRouter $router,
        private NodeProcessorInterface $nodeProcessor,
    )
    {
    }

    public function process(Context $ctx): \Generator
    {
        yield from $this->nodeProcessor->process($ctx);
    }

    public function defineCurrentNode(Context $ctx): string
    {
        return $this->router->route($ctx) ?? $this->key;
    }

    public function getKey(): string
    {
        return $this->key;
    }
}