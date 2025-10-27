<?php

namespace Anymodule\Agentmodule\Services\ProjectSettings;

use Anymodule\Agentmodule\Interface\ProjectApiInterface;
use Anymodule\Agentmodule\Interface\Storage\ProjectSettingsInterface;

class ProjectSettings implements ProjectSettingsInterface
{
    private array $preferredModels = [];

    public function __construct(
        private int                 $projectId,
        private ProjectApiInterface $projectApi,
    )
    {
    }

    public function getPreferredModel(string $taskType): ?string
    {
        if (empty($this->preferredModels)) {
            $this->preferredModels = $this->projectApi->getPreferredModels($this->projectId);
        }

        return $this->preferredModels[$taskType] ?? null;
    }
}