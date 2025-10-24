<?php

namespace Anymodule\Agentmodule\Utils;

use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class ToolCallCounter implements ToolInterface
{
    public $calls = [];
    public function __construct(
        private ToolInterface $tool
    )
    {
    }

    public function execute(array $args): ?\Anymodule\Agentmodule\Entity\ToolResult
    {
        $result = $this->tool->execute($args);
        $this->calls[] = $result;

        return $result;
    }

    public function getProps(): array
    {
        return $this->tool->getProps();
    }

    public function getName(): string
    {
        return $this->tool->getName();
    }
}