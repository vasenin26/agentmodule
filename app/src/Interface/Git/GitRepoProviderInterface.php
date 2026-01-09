<?php

namespace Anymodule\Agentmodule\Interface\Git;

use CzProject\GitPhp\GitRepository;

interface GitRepoProviderInterface
{
    public function getRepo(string $url): GitRepository;
}
