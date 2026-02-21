<?php

namespace Anymodule\Agentmodule\Application\Decorator\Tools;

use Anymodule\Agentmodule\Interface\Tools\FileChangeTrackerInterface;

final class FileChangeTracker implements FileChangeTrackerInterface
{
    private bool $changed = false;

    public function markChanged(): void
    {
        $this->changed = true;
    }

    public function hasChanges(): bool
    {
        return $this->changed;
    }

    public function reset(): void
    {
        $this->changed = false;
    }
}
