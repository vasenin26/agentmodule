<?php

namespace Anymodule\Agentmodule\Services\Summary\Interface;

use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;

interface SummaryAgentFactoryInterface
{

    public function createSummaryAgent(GitRepoProviderInterface $repositoryProvider): ActionContract;
}