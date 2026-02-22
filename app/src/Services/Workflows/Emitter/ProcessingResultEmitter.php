<?php

namespace Anymodule\Agentmodule\Services\Workflows\Emitter;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Services\Workflows\Interface\ResultEmitterInterface;

class ProcessingResultEmitter implements ResultEmitterInterface
{
    public function emitStepResult(Context $ctx, StepResult $stepResult, ProcessHandlerInterface $handler): void
    {
        $contextConversation = $ctx->getContextConversation();
        $handler->handle(new ProcessingResult(
            completed: false,
            answer: null,
            conversation: $contextConversation->conversation,
            context: $contextConversation->context,
            modelName: $stepResult->modelName,
            contextFill: $stepResult->contextFill ?? 0,
            promptTokens: $stepResult->promptTokens ?? 0,
            completionTokens: $stepResult->completionTokens ?? 0,
            totalTokens: $stepResult->totalTokens ?? 0,
            payload: [],
        ));
    }

    public function emitCompleted(Context $ctx, ProcessHandlerInterface $handler): void
    {
        $contextConversation = $ctx->getContextConversation();
        $handler->handle(new ProcessingResult(
            completed: true,
            answer: null,
            conversation: $contextConversation->conversation,
            context: $contextConversation->context,
            modelName: null,
            contextFill: 0,
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0,
            payload: [],
        ));
    }
}
