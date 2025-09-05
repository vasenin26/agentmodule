<?php

namespace Anymodule\Agentmodule\Interface\Git;

interface GitTokenProviderInterface
{

    public function getGitByTask(\Anymodule\Agentmodule\Entity\Task $task): ?string;
}