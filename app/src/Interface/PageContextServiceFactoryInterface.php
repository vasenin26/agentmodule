<?php

namespace Anymodule\Agentmodule\Interface;

interface PageContextServiceFactoryInterface
{
    public function createForProject(int $projectId): PageContextServiceInterface;
}
