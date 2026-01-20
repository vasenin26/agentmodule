<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Application\Tools\Workflow\SwitchToDevelopment;
use Anymodule\Agentmodule\Application\Tools\Workflow\SwitchToTesting;
use Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService;
use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Vasenin26\Conversation\Messages\UserMessage;

class DoAnswer implements NodeProcessorInterface
{
    public function __construct(
        private ChatAgentFactoryInterface $chatAgentFactory,
        private GitRepoProviderInterface  $gitRepoProvider,
    )
    {
    }

    public function process(CodeContext|Context $ctx): \Generator
    {
        // Switch context based on user's last message.
        if ($ctx instanceof CodeContext) {
            $lastUserText = $this->getLastUserMessageText($ctx);
            $intent = $this->detectIntent($lastUserText);

            if ($intent === 'dev') {
                $ctx->startDevelopment();
                yield new StepResult(true);
                return;
            }
            if ($intent === 'test') {
                $ctx->startTesting();
                yield new StepResult(true);
                return;
            }

            $initialCodeFinished = $ctx->codeFinished();
            $initialTestFinished = $ctx->testFinished();

            // Let the agent explicitly switch context via tools if needed.
            $tools = new ToolsProviderService([
                SwitchToDevelopment::NAME => new SwitchToDevelopment($ctx),
                SwitchToTesting::NAME => new SwitchToTesting($ctx),
            ]);

            foreach ($this->chatAgentFactory
                         ->createContextAgent($tools, $this->gitRepoProvider)
                         ->execute($ctx->getContextConversation()) as $processingResult) {
                // if state changed (e.g. tool call), stop and let workflow router choose next node
                if ($ctx->codeFinished() !== $initialCodeFinished || $ctx->testFinished() !== $initialTestFinished) {
                    yield new StepResult(true);
                    return;
                }
                yield new StepResult(false);
            }

            yield new StepResult(true);
            return;
        }

        foreach ($this->chatAgentFactory
                     ->createContextAgent(null, $this->gitRepoProvider)
                     ->execute($ctx->getContextConversation()) as $processingResult) {
            yield new StepResult(false);
        }

        yield new StepResult(true);
    }

    private function getLastUserMessageText(CodeContext $ctx): ?string
    {
        $messages = $ctx->getContextConversation()->conversation->getMessages();
        if (empty($messages)) {
            return null;
        }

        $last = $messages[array_key_last($messages)];
        if ($last instanceof UserMessage) {
            return $last->content;
        }

        return null;
    }

    private function detectIntent(?string $text): ?string
    {
        if ($text === null) {
            return null;
        }

        $normalized = mb_strtolower(trim($text));
        // Replace punctuation with spaces, collapse whitespace
        $normalized = preg_replace('~[^\p{L}\p{N}\s]+~u', ' ', $normalized) ?? $normalized;
        $normalized = preg_replace('~\s+~u', ' ', $normalized) ?? $normalized;
        $normalized = trim($normalized);

        // Development intent
        if (
            str_contains($normalized, 'продолж') ||
            $normalized === 'дальше' ||
            str_contains($normalized, 'continue') ||
            str_contains($normalized, 'go on') ||
            str_contains($normalized, 'keep going')
        ) {
            return 'dev';
        }

        // Testing intent
        if (
            str_contains($normalized, 'протестир') ||
            str_contains($normalized, 'тестир') ||
            str_contains($normalized, 'run tests') ||
            str_contains($normalized, 'test it') ||
            ($normalized === 'test') ||
            ($normalized === 'tests')
        ) {
            return 'test';
        }

        return null;
    }
}