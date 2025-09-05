<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Interface\Git\GitTokenProviderInterface;
use Anymodule\Agentmodule\Interface\TokenProviderInterface;

class RepositoryTokenProvider implements GitTokenProviderInterface
{
    private array $tokens = [];

    public function __construct(
        private TokenProviderInterface $tokenProviderApi
    )
    {

    }

    public function getGitByTask(\Anymodule\Agentmodule\Entity\Task $task): ?string
    {
        if ($this->tokens[$task->projectId]) {
            return $this->tokens[$task->projectId];
        }

        return $this->loadRepositoryToken($task);
    }

    private function loadRepositoryToken(\Anymodule\Agentmodule\Entity\Task $task): string
    {
        $token = $this->tokenProviderApi->getToken($task->projectId);

        return $this->tokens[$task->projectId] = $token;
    }
}