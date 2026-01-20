<?php

namespace Anymodule\Agentmodule\Policy\TaskProcessing;

use Anymodule\Agentmodule\Application\TaskProcessor\Actualization;
use Anymodule\Agentmodule\Application\TaskProcessor\CodeProcessor;
use Anymodule\Agentmodule\Application\TaskProcessor\CodeWorkflow;
use Anymodule\Agentmodule\Application\TaskProcessor\TaskGenerationProcessor;
use Anymodule\Agentmodule\Application\TaskProcessor\TechPlaneGeneration;
use Anymodule\Agentmodule\Application\TaskProcessor\TerminalProcessor;
use Anymodule\Agentmodule\Application\TaskProcessor\TextProcessor;
use Anymodule\Agentmodule\Interface\Task\TaskProcessor;

final class TaskProcessorOrder
{
    /**
     * ВАЖНО:
     * Порядок имеет значение.
     * Процессоры перечислены от наиболее специфичных
     * к наиболее общему (fallback).
     *
     * @return class-string<TaskProcessor>[]
     */
    public static function ordered(): array
    {
        return [
            CodeWorkflow::class,
            CodeProcessor::class,
            Actualization::class,
            TechPlaneGeneration::class,
            TaskGenerationProcessor::class,
            TerminalProcessor::class,
            TextProcessor::class, // fallback
        ];
    }
}
