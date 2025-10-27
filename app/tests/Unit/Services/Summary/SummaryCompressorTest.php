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
        $agentFactory = Mockery::mock(\Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface::class);
        $agentFactory->shouldReceive('createAgent')
            ->andThrow(new \Anymodule\Agentmodule\Services\OpenAIChat\Exception\ContextOverloadException());

        // Создаем мок для ToolServiceFactory
        $toolServiceFactory = Mockery::mock(\Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface::class);
        $toolsBuilder = Mockery::mock(\Anymodule\Agentmodule\Application\ToolsService\ToolsBuilder::class);
        $toolsBuilder->shouldReceive('build')->andReturn(Mockery::mock(\Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService::class));
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
}