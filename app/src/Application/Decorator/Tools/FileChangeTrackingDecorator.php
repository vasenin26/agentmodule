<?php

namespace Anymodule\Agentmodule\Application\Decorator\Tools;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\FileChangeTrackerInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

final class FileChangeTrackingDecorator implements ToolInterface
{
    public function __construct(
        private ToolInterface $tool,
        private FileChangeTrackerInterface $tracker,
    ) {
    }

    public function execute(array $args): ?ToolResult
    {
        $result = $this->tool->execute($args);
        if ($result !== null && $result->status) {
            $this->tracker->markChanged();
        }
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
