<?php

namespace Anymodule\Agentmodule\Interface\Storage;

interface ProjectSettingsInterface
{

    public function getPreferredModel(string $taskType): ?string;
}