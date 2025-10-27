<?php

namespace Anymodule\Agentmodule\Services\ProjectSettings;

use Anymodule\Agentmodule\Interface\ProjectApiInterface;
use Anymodule\Agentmodule\Interface\ProjectSettingsProviderInterface;
use Anymodule\Agentmodule\Interface\Storage\ProjectSettingsInterface;

class SettingProvider implements ProjectSettingsProviderInterface
{
    private array $stores = [];

    public function __construct(
        private ProjectApiInterface $projectApi,
    )
    {
    }

    public function getProjectSetting(int $projectId): ProjectSettingsInterface
    {
        if (!isset($this->stores[$projectId])) {
            $this->stores[$projectId] = new ProjectSettings($projectId, $this->projectApi);
        }

        return $this->stores[$projectId];
    }
}