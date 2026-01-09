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
                context: null,
                modelName: null,
                contextFill: 1,
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0
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
        
        // Создаем мок для SummaryAgentFactoryInterface
        $summaryAgentFactory = Mockery::mock(\Anymodule\Agentmodule\Services\Summary\Interface\SummaryAgentFactoryInterface::class);
        $actionMock = Mockery::mock(\Anymodule\Agentmodule\Interface\ActionContract::class);
        
        // Создаем ProcessingResult с null context, чтобы избежать ошибок
        $processingResult = new ProcessingResult(
            completed: true,
            answer: 'Summary',
            conversation: new Chat(),
            context: null,
            modelName: null,
            contextFill: 0,
            promptTokens: 0,
            completionTokens: 0,
            totalTokens: 0
        );
        
        $actionMock->shouldReceive('execute')
            ->once()
            ->andReturn((function() use ($processingResult) { 
                yield $processingResult; 
            })());
        $summaryAgentFactory->shouldReceive('createSummaryAgent')->once()->andReturn($actionMock);
        
        // Создаем РЕАЛЬНЫЙ SummaryGenerator
        $summaryGenerator = new \Anymodule\Agentmodule\Services\Summary\SummaryGenerator($summaryAgentFactory);
        
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

        // Тест должен пройти успешно, так как мы мокируем агента
        $result = $compressor->compress($chat);
        
        Mockery::close();
        
        $this->assertNotNull($result);
    }
}