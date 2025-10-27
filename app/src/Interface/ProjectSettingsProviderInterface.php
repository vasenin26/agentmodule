<?php

namespace Anymodule\Agentmodule\Interface;

use Anymodule\Agentmodule\Interface\Storage\ProjectSettingsInterface;

interface ProjectSettingsProviderInterface
{

    public function getProjectSetting(int $projectId): ProjectSettingsInterface;
}