<?php

namespace Anymodule\Agentmodule\Application\Workflows;

use Anymodule\Agentmodule\Application\Workflows\Interface\StepInterface;
use Anymodule\Agentmodule\Application\Workflows\Interface\WorkflowWorker;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;

class Worker implements WorkflowWorker
{

    public function process(Task $task, array $workflow, ProcessHandlerInterface $processHandler): void
    {
        $currentStep = $this->defineCurrentStep($task, $workflow);

        while (!is_null($currentStep)) {
            $step = $this->getStepWorker($currentStep, $workflow);
            $result = $step->process($task, $processHandler);

            $currentStep = $this->getNextTest($currentStep, $workflow, $result);
        }
    }

    private function defineCurrentStep(Task $task, array $workflow): ?string
    {
        return array_key_first($workflow);
    }

    private function getStepWorker($currentStep, array $workflow): StepInterface
    {
    }

    private function getNextTest(string $step, array $workflow, DTO\StepResult $result): ?string
    {
        $stepMeta = $workflow[$step];

        if($result->success) {
            return $stepMeta[AbstractWorkflow::SUCCESS] ?? null;
        }

        return $stepMeta[AbstractWorkflow::SUCCESS] ?? null;
    }
}