<?php

namespace Anymodule\Agentmodule\Application\Enum;

enum TaskTypes: string
{
    case SearchRelevantFiles = 'search-relevant-files';
    case TaskPlaning = 'task-planing';
}