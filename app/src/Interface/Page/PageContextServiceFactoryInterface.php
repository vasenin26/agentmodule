<?php

namespace Anymodule\Agentmodule\Interface\Page;

interface PageContextServiceFactoryInterface
{
    public function createForProject(int $projectId): PageContextServiceInterface;
}
