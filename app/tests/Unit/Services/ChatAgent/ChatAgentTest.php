<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ChatAgent;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Services\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Services\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Services\ChatAgent\Exception\ContextOverloadException;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\CharProcessorInterface;
use Anymodule\Agentmodule\Services\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Mockery;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\UserMessage;

class ChatAgentTest extends TestCase
{
    public function testChatAgent(): void
    {
        $conversation = new Chat();

        $processor = new class implements CharProcessorInterface {
            public function contextSize(): int
            {
                return 1_000_000;
            }

            public function process(Chat $chat, ToolsProviderInterface $tools): ChatResultInterface
            {
                return new class implements ChatResultInterface {
                    public function getProcessorAnswer(): ?ProcessorAnswer
                    {
                        //this is agent answer
                        return new ProcessorAnswer('ok');
                    }

                    public function getToolCalls(): \Generator
                    {
                        if (false) yield null;
                    }

                    public function getTokenUsage(): TokenUsage
                    {
                        return new TokenUsage(1000, 1000, 1000);
                    }
                };
            }
        };

        $compressor = new class implements ConversationCompressorInterface {
            public function compress(Conversation $conversation): Conversation
            {
                return $conversation;
            }
        };

        $toolsProvider = new class implements ToolsProviderInterface {
            public function getMeta(): array
            {
                return [];
            }

            public function callTool(string $toolName, string $args): ?ToolResult
            {
                return null;
            }

            public function getTaskTool(): ?ToolInterface
            {
                return null;
            }
        };

        $agent = new ChatAgent($processor, $compressor, $toolsProvider);
        $result = null;

        foreach ($agent->execute($conversation) as $result) {
            if ($result->completed) {
                break;
            }
        }

        $this->assertTrue($result->completed);
        $this->assertEquals('ok', $result->answer);
    }

    public function testAgentCallCompressor()
    {
        $processor = new class implements CharProcessorInterface {

            public function contextSize(): int
            {
                return 1;
            }

            public function process(Chat $chat, ToolsProviderInterface $tools): ChatResultInterface
            {
                if(count($chat->getMessages()) > $this->contextSize()) {
                    throw new ContextOverloadException();
                }

                return new OpenAiResult (
                    'ok',
                    [],
                    0,
                    0,
                    count($chat->getMessages())

                );
            }
        };

        $compressor = Mockery::mock(ConversationCompressorInterface::class);
        $compressor->shouldReceive('compress')
            ->once()
            ->andReturn(new Chat());

        $toolsProvider = Mockery::mock(ToolsProviderInterface::class);
        $toolsProvider->shouldReceive('getMeta')
            ->andReturn([]);

        $agent = new ChatAgent($processor, $compressor, $toolsProvider);

        $conversation = new Chat();
        $conversation->addMessage(new UserMessage('Hello World!'));
        $conversation->addMessage(new UserMessage('Hello World!'));

        $results = iterator_to_array($agent->execute($conversation));

        Mockery::close();

        $this->assertNotEmpty($results);
        $this->assertTrue(end($results)->completed ?? true);
    }
}