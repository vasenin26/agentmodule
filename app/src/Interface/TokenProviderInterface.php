<?php

namespace Anymodule\Agentmodule\Interface;

interface TokenProviderInterface
{

    public function getToken(int $projectId): string;
}