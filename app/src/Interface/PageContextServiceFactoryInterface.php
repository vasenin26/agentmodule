<?php

namespace Anymodule\Agentmodule\Interface;

use App\Interfaces\PageContextServiceInterface;

interface PageContextServiceFactoryInterface
{
    public function createForProject(int $projectId): PageContextServiceInterface;
}
