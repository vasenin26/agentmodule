<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ChatAgent;

use Anymodule\Agentmodule\Application\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Application\ChatAgent\ContextAgent;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ToolCall;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ContextConversationProcessorInterface;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ModelMeta;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\DisappearingMessage;

/**
 * ContextAgent::LOOP_LIMIT / TOOL_CALL_LIMIT are soft guards: they never abort processing,
 * they inject a DisappearingMessage nudge into the conversation and keep going (ContextAgent.php
 * catches ToolCallLimitReachedException/LoopLimitReachedException internally). These tests drive
 * the real limits (not mocks) and stop as soon as that nudge shows up, instead of exhausting a
 * generator that would otherwise keep running forever against these always-call-a-tool fakes.
 */
class ContextAgentGuardsTest extends TestCase
{
    private ConversationCompressorInterface $compressor;
    private ToolsProviderInterface $tools;

    protected function setUp(): void
    {
        $this->compressor = new class implements ConversationCompressorInterface {
            public function compress(Conversation $conversation): Conversation
            {
                return $conversation;
            }
        };

        $this->tools = new class implements ToolsProviderInterface {
            public function getMeta(): array
            {
                return [];
            }

            public function callTool(string $toolName, string $args): ?ToolResult
            {
                return new ToolResult(true, 'ok');
            }

            public function getTaskTool(): ?ToolInterface
            {
                return null;
            }
        };
    }

    public function testToolCallLimitInjectsSoftWarningAfterThreeIdenticalCallsInOneStep(): void
    {
        $processor = new class implements ContextConversationProcessorInterface {
            public function contextSize(): int
            {
                return 1_000_000;
            }

            public function getModelMeta(): ModelMeta
            {
                return new ModelMeta('test-model', 1_000_000);
            }

            public function process(ContextConversation $contextConversation, ?ToolsProviderInterface $tools): ChatResultInterface
            {
                return new class implements ChatResultInterface {
                    public function getProcessorAnswer(): ?ProcessorAnswer
                    {
                        return new ProcessorAnswer('working');
                    }

                    public function getToolCalls(): \Generator
                    {
                        // Same tool name called 3 times in a single LLM turn trips TOOL_CALL_LIMIT (=3).
                        yield new ToolCall('call-1', 'noop-tool', '{}');
                        yield new ToolCall('call-2', 'noop-tool', '{}');
                        yield new ToolCall('call-3', 'noop-tool', '{}');
                    }

                    public function getTokenUsage(): TokenUsage
                    {
                        return new TokenUsage(1, 1, 1);
                    }
                };
            }
        };

        $contextAgent = new ContextAgent($processor, $this->compressor, $this->tools);
        $agent = new ChatAgent($contextAgent);

        $conversation = new Chat();
        $warning = $this->runUntilDisappearingMessage($agent, $conversation);

        $this->assertNotNull($warning, 'Expected a DisappearingMessage warning to be injected');
        $this->assertStringContainsString('confused', $warning->content);
    }

    public function testLoopLimitInjectsSoftWarningAfterStepBudgetExhausted(): void
    {
        $processor = new class implements ContextConversationProcessorInterface {
            public function contextSize(): int
            {
                return 1_000_000;
            }

            public function getModelMeta(): ModelMeta
            {
                return new ModelMeta('test-model', 1_000_000);
            }

            public function process(ContextConversation $contextConversation, ?ToolsProviderInterface $tools): ChatResultInterface
            {
                return new class implements ChatResultInterface {
                    public function getProcessorAnswer(): ?ProcessorAnswer
                    {
                        return new ProcessorAnswer('working');
                    }

                    public function getToolCalls(): \Generator
                    {
                        // Same single tool call every step resets TOOL_CALL_LIMIT's counter each
                        // time (see ContextAgent.php), so only LOOP_LIMIT (=50 steps) can trip here.
                        yield new ToolCall('call-1', 'noop-tool', '{}');
                    }

                    public function getTokenUsage(): TokenUsage
                    {
                        return new TokenUsage(1, 1, 1);
                    }
                };
            }
        };

        $contextAgent = new ContextAgent($processor, $this->compressor, $this->tools);
        $agent = new ChatAgent($contextAgent);

        $conversation = new Chat();
        $warning = $this->runUntilDisappearingMessage($agent, $conversation, maxResults: 500);

        $this->assertNotNull($warning, 'Expected a DisappearingMessage warning to be injected');
        $this->assertStringContainsString('working too long', $warning->content);
    }

    private function runUntilDisappearingMessage(
        ChatAgent $agent,
        Conversation $conversation,
        int $maxResults = 20,
    ): ?DisappearingMessage {
        $seen = 0;
        foreach ($agent->execute($conversation) as $result) {
            $seen++;

            foreach ($conversation->getMessages() as $message) {
                if ($message instanceof DisappearingMessage) {
                    return $message;
                }
            }

            if ($seen >= $maxResults) {
                break;
            }
        }

        return null;
    }
}
