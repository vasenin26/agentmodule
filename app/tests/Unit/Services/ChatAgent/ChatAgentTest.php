<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ChatAgent;

use Anymodule\Agentmodule\Application\ChatAgent\ChatAgent;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\ProcessorAnswer;
use Anymodule\Agentmodule\Application\ChatAgent\DTO\TokenUsage;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatProcessorInterface;
use Anymodule\Agentmodule\Application\ChatAgent\Interface\ChatResultInterface;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolsProviderInterface;
use Anymodule\Agentmodule\Services\OpenAIChat\DTO\OpenAiResult;
use Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException;
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

        $processor = new class implements ChatProcessorInterface {
            public function contextSize(): int
            {
                return 1_000_000;
            }

            public function getModelMeta(): \Anymodule\Agentmodule\Entity\ModelMeta
            {
                return new \Anymodule\Agentmodule\Entity\ModelMeta('test-model', 1_000_000);
            }

            public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface
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
        $processor = new class implements ChatProcessorInterface {

            public function contextSize(): int
            {
                return 1;
            }

            public function getModelMeta(): \Anymodule\Agentmodule\Entity\ModelMeta
            {
                return new \Anymodule\Agentmodule\Entity\ModelMeta('test-model', 1);
            }

            public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface
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

    public function testContextOverload(): void
    {
        // Создаем процессор, который выбрасывает исключение при большом контексте
        $processor = new class implements ChatProcessorInterface {
            public function contextSize(): int
            {
                return 1000; // Маленький лимит контекста
            }

            public function getModelMeta(): \Anymodule\Agentmodule\Entity\ModelMeta
            {
                return new \Anymodule\Agentmodule\Entity\ModelMeta('test-model', 1000);
            }

            public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface
            {
                // Симулируем переполнение контекста
                $messageCount = count($chat->getMessages());
                if ($messageCount > 5) { // Если больше 5 сообщений - переполнение
                    throw new ContextOverloadException();
                }

                return new OpenAiResult(
                    'ok',
                    [],
                    0,
                    0,
                    $messageCount
                );
            }
        };

        // Создаем компрессор, который НЕ РЕШАЕТ проблему - передает тот же большой контекст
        $compressor = new class implements ConversationCompressorInterface {
            public function compress(Conversation $conversation): Conversation
            {
                // ПРОБЛЕМА: компрессор возвращает тот же большой контекст!
                // В реальности SummaryCompressor передает весь контекст в SummaryGenerator
                // который снова передает его в ChatAgent, что снова вызовет переполнение
                
                $compressed = new Chat();
                
                // Добавляем ВСЕ сообщения из оригинального чата (проблема!)
                foreach ($conversation->getMessages() as $message) {
                    $compressed->addMessage($message);
                }
                
                // Добавляем еще одно сообщение (делаем контекст еще больше!)
                $compressed->addMessage(new UserMessage('Summary generation prompt'));
                
                return $compressed;
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

        // Создаем чат с большим контекстом (больше лимита)
        $conversation = new Chat();
        for ($i = 0; $i < 10; $i++) {
            $conversation->addMessage(new UserMessage("Message $i"));
        }

        // Ожидаем, что агент выбросит исключение даже после компрессии
        $this->expectException(ContextOverloadException::class);
        
        $results = iterator_to_array($agent->execute($conversation));
    }

    public function testContextOverloadWithRealScenario(): void
    {
        // Тест, который точно воспроизводит проблему с SummaryCompressor
        $processor = new class implements ChatProcessorInterface {
            public function contextSize(): int
            {
                return 1000; // Маленький лимит контекста
            }

            public function getModelMeta(): \Anymodule\Agentmodule\Entity\ModelMeta
            {
                return new \Anymodule\Agentmodule\Entity\ModelMeta('test-model', 1000);
            }

            public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface
            {
                // Симулируем переполнение контекста
                $messageCount = count($chat->getMessages());
                if ($messageCount > 5) { // Если больше 5 сообщений - переполнение
                    throw new ContextOverloadException();
                }

                return new OpenAiResult(
                    'ok',
                    [],
                    0,
                    0,
                    $messageCount
                );
            }
        };

        // Компрессор, который симулирует реальную проблему SummaryCompressor
        $compressor = new class implements ConversationCompressorInterface {
            public function compress(Conversation $conversation): Conversation
            {
                // РЕАЛЬНАЯ ПРОБЛЕМА: SummaryCompressor создает SummaryGenerator
                // который передает ВЕСЬ контекст в ChatAgent для генерации саммари
                // Это снова вызывает переполнение!
                
                $compressed = new Chat();
                
                // Сохраняем только системные сообщения (как в SummaryCompressor)
                foreach ($conversation->getMessages() as $message) {
                    if ($message instanceof \Vasenin26\Conversation\Messages\SystemMessage ||
                        $message instanceof \Vasenin26\Conversation\Messages\UserTaskMessage) {
                        $compressed->addMessage($message);
                    }
                }
                
                // НО! SummaryGenerator передает ВЕСЬ оригинальный контекст в агента
                // для генерации саммари - это и есть проблема!
                
                // Симулируем это: добавляем весь оригинальный контекст + промпт
                foreach ($conversation->getMessages() as $message) {
                    $compressed->addMessage($message);
                }
                $compressed->addMessage(new UserMessage('Summary generation prompt'));
                
                return $compressed;
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

        // Создаем чат с большим контекстом (больше лимита)
        $conversation = new Chat();
        for ($i = 0; $i < 10; $i++) {
            $conversation->addMessage(new UserMessage("Message $i"));
        }

        // Ожидаем, что агент выбросит исключение даже после компрессии
        $this->expectException(ContextOverloadException::class);
        
        $results = iterator_to_array($agent->execute($conversation));
    }

    public function testContextOverloadDemonstratesProblem(): void
    {
        // Этот тест демонстрирует, что проблема НЕ в логике ChatAgent
        // а в том, что SummaryCompressor передает большой контекст в SummaryGenerator
        
        $processor = new class implements ChatProcessorInterface {
            public function contextSize(): int
            {
                return 1000; // Маленький лимит контекста
            }

            public function getModelMeta(): \Anymodule\Agentmodule\Entity\ModelMeta
            {
                return new \Anymodule\Agentmodule\Entity\ModelMeta('test-model', 1000);
            }

            public function process(Conversation $chat, ?ToolsProviderInterface $tools): ChatResultInterface
            {
                // Симулируем переполнение контекста
                $messageCount = count($chat->getMessages());
                if ($messageCount > 5) { // Если больше 5 сообщений - переполнение
                    throw new ContextOverloadException();
                }

                return new OpenAiResult(
                    'ok',
                    [],
                    0,
                    0,
                    $messageCount
                );
            }
        };

        // Компрессор, который РЕШАЕТ проблему (правильная реализация)
        $workingCompressor = new class implements ConversationCompressorInterface {
            public function compress(Conversation $conversation): Conversation
            {
                // ПРАВИЛЬНАЯ РЕАЛИЗАЦИЯ: обрезаем контекст до безопасного размера
                $compressed = new Chat();
                
                // Берем только первые 3 сообщения (безопасный размер)
                $messages = $conversation->getMessages();
                $safeMessages = array_slice($messages, 0, 3);
                
                foreach ($safeMessages as $message) {
                    $compressed->addMessage($message);
                }
                
                return $compressed;
            }
        };

        // Компрессор, который НЕ РЕШАЕТ проблему (проблемная реализация)
        $brokenCompressor = new class implements ConversationCompressorInterface {
            public function compress(Conversation $conversation): Conversation
            {
                // ПРОБЛЕМНАЯ РЕАЛИЗАЦИЯ: передаем весь контекст + еще больше
                $compressed = new Chat();
                
                // Передаем ВСЕ сообщения + добавляем еще
                foreach ($conversation->getMessages() as $message) {
                    $compressed->addMessage($message);
                }
                
                // Добавляем еще сообщения (делаем контекст еще больше!)
                for ($i = 0; $i < 5; $i++) {
                    $compressed->addMessage(new UserMessage("Additional message $i"));
                }
                
                return $compressed;
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

        // Создаем чат с большим контекстом
        $conversation = new Chat();
        for ($i = 0; $i < 10; $i++) {
            $conversation->addMessage(new UserMessage("Message $i"));
        }

        // Тест 1: Рабочий компрессор должен работать
        $workingAgent = new ChatAgent($processor, $workingCompressor, $toolsProvider);
        $workingResults = iterator_to_array($workingAgent->execute($conversation));
        $this->assertNotEmpty($workingResults);
        $this->assertTrue(end($workingResults)->completed);

        // Тест 2: Проблемный компрессор должен падать
        $brokenAgent = new ChatAgent($processor, $brokenCompressor, $toolsProvider);
        $this->expectException(ContextOverloadException::class);
        $brokenResults = iterator_to_array($brokenAgent->execute($conversation));
    }
}