<?php

namespace Anymodule\Agentmodule\Services\GigaChat\Interface;

use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;

interface GigaClientInterface
{

    public function process(ModelMeta $modelMeta, ?ToolsProviderInterface $tools, $messages);
}