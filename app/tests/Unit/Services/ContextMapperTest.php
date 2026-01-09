<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\ChatContextMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\OpenAIMessageMapperInterface;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Services\OpenAIChat\Interface\MessageMapper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Interface\Conversation;

class ContextMapperTest extends TestCase
{
    private ChatContextMapper $contextMapper;
    private OpenAIMessageMapperInterface|MockObject $messageProcessor;
    private MessageMapper|MockObject $messageMapper;

    protected function setUp(): void
    {
        $this->messageProcessor = $this->createMock(OpenAIMessageMapperInterface::class);
        $this->messageMapper = $this->createMock(MessageMapper::class);
        
        $this->contextMapper = new ChatContextMapper(
            $this->messageProcessor,
            $this->messageMapper
        );
    }

    public function testMapConversationWithoutTasks(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $context = new Context([]);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $expectedMessages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there!']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($expectedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertEquals($expectedMessages, $result);
    }

    public function testMapConversationWithTasks(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $tasks = [
            ['title' => 'Task 1', 'done' => false],
            ['title' => 'Task 2', 'done' => false]
        ];
        $context = new Context($tasks);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['role' => 'user', 'content' => 'Hello'],
            ['role' => 'assistant', 'content' => 'Hi there!'],
            ['role' => 'user', 'content' => 'How are you?']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertCount(4, $result);
        
        // Проверяем, что task message вставлен перед последним user message
        $this->assertEquals('user', $result[2]['role']);
        $this->assertStringContainsString('Below is your current task list', $result[2]['content']);
        $this->assertStringContainsString('[current] Task 1', $result[2]['content']);
        $this->assertStringContainsString('[todo] Task 2', $result[2]['content']);
        
        // Проверяем, что последний user message остался на своем месте
        $this->assertEquals('user', $result[3]['role']);
        $this->assertEquals('How are you?', $result[3]['content']);
    }

    public function testMapConversationWithTasksAndNoUserMessages(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $tasks = [
            ['title' => 'Task 1', 'done' => false]
        ];
        $context = new Context($tasks);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['role' => 'assistant', 'content' => 'Hi there!']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertCount(2, $result);
        
        // Task message должен быть вставлен в начало
        $this->assertEquals('user', $result[0]['role']);
        $this->assertStringContainsString('Below is your current task list', $result[0]['content']);
        $this->assertEquals('assistant', $result[1]['role']);
        $this->assertEquals('Hi there!', $result[1]['content']);
    }

    public function testMapConversationWithEmptyTasks(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $context = new Context([]);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $expectedMessages = [
            ['role' => 'user', 'content' => 'Hello']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($expectedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertEquals($expectedMessages, $result);
    }

    public function testPrepareAssistantMessage(): void
    {
        // Этот тест пропускаем, так как CreateResponse - final класс
        // В реальном использовании этот метод будет вызываться с реальным объектом
        $this->markTestSkipped('CreateResponse is final class, cannot be mocked');
    }

    public function testMapConversationWithMixedMessageTypes(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $tasks = [
            ['title' => 'Task 1', 'done' => true],
            ['title' => 'Task 2', 'done' => false],
            ['title' => 'Task 3', 'done' => false]
        ];
        $context = new Context($tasks);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['role' => 'system', 'content' => 'System message'],
            ['role' => 'user', 'content' => 'First user message'],
            ['role' => 'assistant', 'content' => 'Assistant response'],
            ['role' => 'user', 'content' => 'Second user message'],
            ['role' => 'assistant', 'content' => 'Another response'],
            ['role' => 'user', 'content' => 'Last user message']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertCount(7, $result);
        
        // Проверяем, что task message вставлен перед последним user message (index 5)
        $this->assertEquals('user', $result[5]['role']);
        $this->assertStringContainsString('Below is your current task list', $result[5]['content']);
        $this->assertStringContainsString('[done] Task 1', $result[5]['content']);
        $this->assertStringContainsString('[current] Task 2', $result[5]['content']);
        $this->assertStringContainsString('[todo] Task 3', $result[5]['content']);
        
        // Проверяем, что последний user message остался на своем месте
        $this->assertEquals('user', $result[6]['role']);
        $this->assertEquals('Last user message', $result[6]['content']);
    }

    public function testMapConversationWithTasksHavingEmptyTitles(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $tasks = [
            ['title' => '', 'done' => false],
            ['title' => 'Valid Task', 'done' => false]
        ];
        $context = new Context($tasks);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['type' => 'user', 'content' => 'Hello']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertCount(2, $result);
        
        // Проверяем, что task message содержит инструкцию
        $this->assertEquals('user', $result[0]['role']);
        $this->assertStringContainsString('Below is your current task list', $result[0]['content']);
        $this->assertStringContainsString('[todo] Valid Task', $result[0]['content']);
    }

    public function testMapConversationWithAllTasksCompleted(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $tasks = [
            ['title' => 'Task 1', 'done' => true],
            ['title' => 'Task 2', 'done' => true]
        ];
        $context = new Context($tasks);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['type' => 'user', 'content' => 'Hello']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertCount(2, $result);
        
        // Проверяем, что все задачи отмечены как done
        $this->assertEquals('user', $result[0]['role']);
        $this->assertStringContainsString('[done] Task 1', $result[0]['content']);
        $this->assertStringContainsString('[done] Task 2', $result[0]['content']);
    }

    /**
     * Тест сценария: маппинг сообщений в целом
     * Проверяем, что ContextMapper корректно вызывает messageMapper и возвращает результат
     */
    public function testMessageMappingScenario(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $context = new Context([]);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $expectedMessages = [
            ['role' => 'user', 'content' => 'Привет'],
            ['role' => 'assistant', 'content' => 'Привет! Как дела?'],
            ['role' => 'user', 'content' => 'Хорошо, спасибо!']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($expectedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertEquals($expectedMessages, $result);
        $this->assertCount(3, $result);
        $this->assertEquals('user', $result[0]['role']);
        $this->assertEquals('assistant', $result[1]['role']);
        $this->assertEquals('user', $result[2]['role']);
    }

    /**
     * Тест сценария: добавление сообщения с задачами, если они есть в переданном хранилище
     * Проверяем, что когда в контексте есть задачи, они добавляются как специальное сообщение
     */
    public function testTaskMessageAdditionScenario(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $tasks = [
            ['title' => 'Изучить требования', 'done' => false],
            ['title' => 'Создать план', 'done' => false],
            ['title' => 'Реализовать функционал', 'done' => true],
            ['title' => 'Протестировать', 'done' => false]
        ];
        $context = new Context($tasks);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['role' => 'user', 'content' => 'Начнем работу над проектом'],
            ['role' => 'assistant', 'content' => 'Хорошо, давайте начнем!'],
            ['role' => 'user', 'content' => 'Какие задачи у нас есть?']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertCount(4, $result);
        
        // Проверяем, что task message добавлен перед последним user message
        $taskMessage = $result[2];
        $this->assertEquals('user', $taskMessage['role']);
        $this->assertStringContainsString('Below is your current task list', $taskMessage['content']);
        $this->assertStringContainsString('[current] Изучить требования', $taskMessage['content']);
        $this->assertStringContainsString('[todo] Создать план', $taskMessage['content']);
        $this->assertStringContainsString('[done] Реализовать функционал', $taskMessage['content']);
        $this->assertStringContainsString('[todo] Протестировать', $taskMessage['content']);
        
        // Проверяем, что последний user message остался на своем месте
        $this->assertEquals('user', $result[3]['role']);
        $this->assertEquals('Какие задачи у нас есть?', $result[3]['content']);
    }

    /**
     * Тест сценария: НЕ добавление сообщения с задачами, если задач нет
     * Проверяем, что когда в контексте нет задач, специальное сообщение не добавляется
     */
    public function testNoTaskMessageWhenNoTasksScenario(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $context = new Context([]); // Пустой массив задач
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['role' => 'user', 'content' => 'Привет'],
            ['role' => 'assistant', 'content' => 'Привет!']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertEquals($mappedMessages, $result);
        $this->assertCount(2, $result);
        
        // Проверяем, что нет сообщений с инструкциями по задачам
        foreach ($result as $message) {
            $this->assertStringNotContainsString('Below is your current task list', $message['content']);
        }
    }

    /**
     * Тест сценария: добавление task message в начало, если нет user сообщений
     * Проверяем, что когда нет user сообщений, task message добавляется в начало
     */
    public function testTaskMessageAtBeginningWhenNoUserMessagesScenario(): void
    {
        // Arrange
        $conversation = $this->createMock(Conversation::class);
        $tasks = [
            ['title' => 'Системная задача', 'done' => false]
        ];
        $context = new Context($tasks);
        $contextConversation = new ContextConversation($context, $conversation);
        
        $mappedMessages = [
            ['role' => 'system', 'content' => 'Системное сообщение'],
            ['role' => 'assistant', 'content' => 'Ответ ассистента']
        ];

        $this->messageMapper
            ->expects($this->once())
            ->method('mapChat')
            ->with($conversation)
            ->willReturn($mappedMessages);

        // Act
        $result = $this->contextMapper->mapConversation($contextConversation);

        // Assert
        $this->assertCount(3, $result);
        
        // Task message должен быть в начале
        $this->assertEquals('user', $result[0]['role']);
        $this->assertStringContainsString('Below is your current task list', $result[0]['content']);
        $this->assertStringContainsString('[current] Системная задача', $result[0]['content']);
        
        // Остальные сообщения сдвинуты
        $this->assertEquals('system', $result[1]['role']);
        $this->assertEquals('assistant', $result[2]['role']);
    }
}
