<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\Summary;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ChatSummaryGeneratorInterface;
use Anymodule\Agentmodule\Services\Summary\SummaryCompressor;
use Mockery;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\DisappearingMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;
use Vasenin26\Conversation\Messages\UserTaskMessage;

class SummaryCompressorTest extends TestCase
{
    public function test_compress(): void
    {
        $compressedAnswer = 'Compressed answer';
        $summary = Mockery::mock(ChatSummaryGeneratorInterface::class);

        $summary->shouldReceive('generate')
            ->once()
            ->andYield(new ProcessingResult(
                completed: true,
                answer: $compressedAnswer,
                conversation: new Chat(),
                contextFill: 1,
            ));

        $compressor = new SummaryCompressor($summary);

        $chat = new Chat();

        $systemMessage = new SystemMessage('System PROMPT');
        $userTaskMessage = new UserTaskMessage('Important User Task');
        $userMessage = new UserMessage('Important User Task');
        $assistantMessage = new AssistantMessage('Assistant answer', []);
        $toolResultMessage = new ToolMessage(true, 'id', 'test', '[]', 'ok');

        $chat->addMessage($systemMessage);
        $chat->addMessage($userTaskMessage);
        $chat->addMessage($userMessage);
        $chat->addMessage($assistantMessage);
        $chat->addMessage($toolResultMessage);

        $result = $compressor->compress($chat);

        Mockery::close();

        $messages = $result->getMessages();

        $this->assertContains($systemMessage, $messages);
        $this->assertContains($userTaskMessage, $messages);
        $this->assertNotContains($userMessage, $messages);
        $this->assertNotContains($assistantMessage, $messages);
        $this->assertNotContains($toolResultMessage, $messages);

        $compressedMessage = $messages[count($messages) - 2];
        $lastMessage = $messages[count($messages) - 1];

        $this->assertInstanceOf(AssistantMessage::class, $compressedMessage);
        $this->assertInstanceOf(DisappearingMessage::class, $lastMessage);

        $this->assertEquals($compressedAnswer, $compressedMessage->content);
    }

    public function test_compress_with_real_summary_generator(): void
    {
        // Тест, который использует РЕАЛЬНЫЙ SummaryGenerator вместо мока
        // и демонстрирует проблему с большими контекстами
        
        // Создаем мок для ChatAgentFactory, который будет выбрасывать исключение
        $agentFactory = Mockery::mock(\Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface::class);
        $agentFactory->shouldReceive('createAgent')
            ->andThrow(new \Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException());

        // Создаем мок для ToolServiceFactory
        $toolServiceFactory = Mockery::mock(\Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface::class);
        $toolsBuilder = Mockery::mock(\Anymodule\Agentmodule\Services\ToolsService\ToolsBuilder::class);
        $toolsBuilder->shouldReceive('build')->andReturn(Mockery::mock(\Anymodule\Agentmodule\Services\ToolsService\ToolsProviderService::class));
        $toolServiceFactory->shouldReceive('createToolsBuilder')->andReturn($toolsBuilder);

        // Создаем РЕАЛЬНЫЙ SummaryGenerator
        $summaryGenerator = new \Anymodule\Agentmodule\Services\Summary\SummaryGenerator($agentFactory, $toolServiceFactory);
        
        // Создаем SummaryCompressor с реальным SummaryGenerator
        $compressor = new SummaryCompressor($summaryGenerator);

        // Создаем чат с большим контекстом
        $chat = new Chat();
        $systemMessage = new SystemMessage('System PROMPT');
        $userTaskMessage = new UserTaskMessage('Important User Task');
        
        $chat->addMessage($systemMessage);
        $chat->addMessage($userTaskMessage);
        
        // Добавляем много сообщений, чтобы создать большой контекст
        for ($i = 0; $i < 100; $i++) {
            $chat->addMessage(new UserMessage("User message $i with some content"));
            $chat->addMessage(new AssistantMessage("Assistant response $i with detailed information", []));
        }

        // Ожидаем, что SummaryCompressor выбросит исключение
        // потому что SummaryGenerator передает весь большой контекст в ChatAgent
        $this->expectException(\Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException::class);
        
        $result = $compressor->compress($chat);
        
        Mockery::close();
    }

    public function test_compress_demonstrates_architectural_problem(): void
    {
        // КРАСНЫЙ ТЕСТ: Этот тест должен ПАДАТЬ и показывать архитектурную проблему
        // SummaryCompressor НЕ ДОЛЖЕН работать с большими контекстами из-за проблемы в SummaryGenerator
        
        // Создаем SummaryGenerator, который симулирует реальную проблему
        $problematicSummaryGenerator = new class implements ChatSummaryGeneratorInterface {
            public function generate(\Vasenin26\Conversation\Interface\Conversation $conversation): \Generator
            {
                // ПРОБЛЕМА: SummaryGenerator получает весь большой контекст
                // и передает его в ChatAgent для генерации саммари
                // Это снова вызывает переполнение контекста!
                
                $messageCount = count($conversation->getMessages());
                
                // Если контекст большой - выбрасываем исключение
                if ($messageCount > 10) {
                    throw new \Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException();
                }
                
                // Если контекст маленький - генерируем саммари
                yield new \Anymodule\Agentmodule\Entity\ProcessingResult(
                    completed: true,
                    answer: "Summary of $messageCount messages",
                    conversation: new \Vasenin26\Conversation\Chat(),
                    contextFill: 0,
                );
            }
        };

        $compressor = new SummaryCompressor($problematicSummaryGenerator);

        // Создаем чат с большим контекстом
        $chat = new Chat();
        $systemMessage = new SystemMessage('System PROMPT');
        $userTaskMessage = new UserTaskMessage('Important User Task');
        
        $chat->addMessage($systemMessage);
        $chat->addMessage($userTaskMessage);
        
        // Добавляем много сообщений (больше 10)
        for ($i = 0; $i < 20; $i++) {
            $chat->addMessage(new UserMessage("User message $i"));
            $chat->addMessage(new AssistantMessage("Assistant response $i", []));
        }

        // КРАСНЫЙ ТЕСТ: Ожидаем, что SummaryCompressor РАБОТАЕТ
        // Но он должен ПАДАТЬ из-за архитектурной проблемы!
        $result = $compressor->compress($chat);
        
        // Эти утверждения НЕ ДОЛЖНЫ выполниться, потому что тест упадет выше
        $this->assertNotNull($result, 'SummaryCompressor должен был сжать контекст');
        $this->assertInstanceOf(\Vasenin26\Conversation\Interface\Conversation::class, $result);
        
        $messages = $result->getMessages();
        $this->assertNotEmpty($messages, 'Сжатый контекст должен содержать сообщения');
        
        // Проверяем, что системные сообщения сохранены
        $this->assertContains($systemMessage, $messages);
        $this->assertContains($userTaskMessage, $messages);
        
        // Проверяем, что есть саммари
        $hasSummary = false;
        foreach ($messages as $message) {
            if ($message instanceof \Vasenin26\Conversation\Messages\AssistantMessage) {
                $hasSummary = true;
                break;
            }
        }
        $this->assertTrue($hasSummary, 'Должно быть сгенерировано саммари');
    }
}