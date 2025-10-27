<?php

namespace Anymodule\Agentmodule\Interface;

interface ProjectApiInterface
{
    /**
     * @param int $projectId
     * @return array - task-type = model-name
     */
    public function getPreferredModels(int $projectId): array;
}