<?php

namespace Anymodule\Agentmodule\Interface;

interface ToolInterface
{
    public function execute(array $args): ?string;
    public function getProps($name): array;
}
