<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\Page\PageApi;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceInterface;
use Anymodule\Agentmodule\Services\PageContext\PageProvider;

class PageContextProviderFactory implements PageContextServiceFactoryInterface
{
    private array $pageProviders = [];

    public function __construct(
        private PageApi $api,
    )
    {
    }

    public function createForProject(int $projectId): PageContextServiceInterface
    {
        if(!isset($this->pageProviders[$projectId])) {
            return $this->pageProviders[$projectId] = new PageProvider($this->api, $projectId);
        }

        return $this->pageProviders[$projectId];
    }
}