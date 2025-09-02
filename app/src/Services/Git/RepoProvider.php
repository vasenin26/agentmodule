<?php

namespace Anymodule\Agentmodule\Services\Git;

use Anymodule\Agentmodule\Interface\GitRepoProviderInterface;
use CzProject\GitPhp\GitRepository;

class RepoProvider implements GitRepoProviderInterface
{

    public function getRepo(string $url): GitRepository
    {
        // TODO: Implement getRepo() method.
    }
}