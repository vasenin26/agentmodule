<?php

namespace Anymodule\Agentmodule\Interface;

use CzProject\GitPhp\GitRepository;

interface GitRepoProviderInterface
{

    public function getRepo(string $url): GitRepository;
}
