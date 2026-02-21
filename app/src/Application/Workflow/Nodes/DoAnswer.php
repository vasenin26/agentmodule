<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Application\Tools\Workflow\SwitchToDevelopment;
use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Anymodule\Agentmodule\Application\Logger\Log;

class DoAnswer implements NodeProcessorInterface
{
    public function __construct(
        private ChatAgentFactoryInterface $chatAgentFactory,
        private GitRepoProviderInterface  $gitRepoProvider,
        private ToolServiceFactoryInterface  $toolsFactory,
    )
    {
    }

    public function process(CodeContext|Context $ctx): \Generator
    {
        Log::info("DoAnswer node processing");

        if ($ctx instanceof CodeContext) {
            $tools = $this->toolsFactory->createToolsBuilder()
                ->withGit($this->gitRepoProvider)
                ->withTools([
                    new SwitchToDevelopment($ctx),
                ])
                ->build();

            $agent = $this->chatAgentFactory->createContextAgent($tools, $this->gitRepoProvider);

            foreach ($agent->execute($ctx->getContextConversation()) as $processingResult) {
                if ($processingResult->completed && $ctx->getRequestedTransition() !== null) {
                    Log::info("Context changed (requestedTransition set), stop DoAnswer processing");
                    yield new StepResult(finished: true);
                    return;
                }

                yield new StepResult(
                    finished: false,
                    promptTokens: $processingResult->promptTokens,
                    completionTokens: $processingResult->completionTokens,
                    totalTokens: $processingResult->totalTokens,
                    modelName: $processingResult->modelName,
                    contextFill: $processingResult->contextFill,
                );
            }
        }

        Log::info("DoAnswer node finished");

        yield new StepResult(true);
    }
}
