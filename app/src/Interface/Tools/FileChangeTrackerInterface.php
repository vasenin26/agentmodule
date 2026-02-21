<?php

namespace Anymodule\Agentmodule\Interface\Tools;

interface FileChangeTrackerInterface
{
    public function markChanged(): void;

    public function hasChanges(): bool;

    public function reset(): void;
}
