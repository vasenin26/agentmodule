<?php

namespace Anymodule\Agentmodule\Services\ProjectSettings;

use Anymodule\Agentmodule\Interface\ProjectApiInterface;
use Anymodule\Agentmodule\Interface\Storage\ProjectSettingsInterface;
use Anymodule\Agentmodule\Utils\Log;

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
            Log::info("Loaded project {$this->projectId} preferred models", $this->preferredModels);
        }

        return $this->preferredModels[$taskType] ?? null;
    }
}