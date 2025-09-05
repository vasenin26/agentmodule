<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Interface\TokenProviderInterface;

class EnvTokenStorage implements TokenProviderInterface
{

    public function getToken(int $projectId): string
    {
        return getenv('GITHUB_TOKEN');
    }
}