<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\PageApi;
use Anymodule\Agentmodule\Interface\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\PageContextServiceInterface;
use Anymodule\Agentmodule\Services\PageContext\PageProvider;

class PageContextProviderFactory implements PageContextServiceFactoryInterface
{
    public function __construct(
        private PageApi $api,
    )
    {
    }

    public function createForProject(int $projectId): PageContextServiceInterface
    {
        return new PageProvider($this->api, $projectId);
    }
}