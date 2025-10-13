<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services;

use Anymodule\Agentmodule\Application\Conversation\ConversationSlice;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ChatSummaryGeneratorInterface;
use Anymodule\Agentmodule\Services\Summary\SummaryCompressor;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserMessage;
use Vasenin26\Conversation\Messages\UserTaskMessage;

class SimpleCompressorTest extends TestCase
{
    private ChatSummaryGeneratorInterface $summaryGenerator;
    private SummaryCompressor $compressor;

    protected function setUp(): void
    {
        $this->summaryGenerator = $this->createMock(ChatSummaryGeneratorInterface::class);
        $this->compressor = new SummaryCompressor($this->summaryGenerator);
    }

    public function testCompressFiltersMessagesCorrectly(): void
    {
        $conversation = $this->createMock(Conversation::class);
        
        // Создаем различные типы сообщений
        $systemMessage = new SystemMessage('System instruction');
        $userMessage = new UserMessage('User message'); // Не должно сохраняться
        $userTaskMessage = new UserTaskMessage('User task');
        $assistantMessageWithContent = new AssistantMessage('Assistant response', []); // Не должно сохраняться
        $assistantMessageEmpty = new AssistantMessage('', []); // Не должно сохраняться
        
        $messages = [
            $systemMessage,
            $userMessage,
            $userTaskMessage,
            $assistantMessageWithContent,
            $assistantMessageEmpty
        ];
        
        $conversation->method('getMessages')->willReturn($messages);
        
        // Настраиваем мок для summaryGenerator
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator());
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressAddsSliceMessage(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getMessages')->willReturn([]);
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator());
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressProcessesSummaryGeneratorResults(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getMessages')->willReturn([]);
        
        $summaryResults = [
            new ProcessingResult(
                completed: false,
                answer: 'Partial summary',
                conversation: $conversation,
                promptTokens: 10,
                completionTokens: 5,
                contextFill: 0,
                totalTokens: 15
            ),
            new ProcessingResult(
                completed: true,
                answer: 'Complete summary',
                conversation: $conversation,
                contextFill: 0,
                promptTokens: 20,
                completionTokens: 10,
                totalTokens: 30
            )
        ];
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator($summaryResults));
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressHandlesEmptyAssistantMessages(): void
    {
        $conversation = $this->createMock(Conversation::class);
        
        $messages = [
            new AssistantMessage('', []), // AssistantMessage больше не сохраняются
            new AssistantMessage('   ', []), // AssistantMessage больше не сохраняются
            new AssistantMessage('Valid content', []), // AssistantMessage больше не сохраняются
            new SystemMessage('System message'), // Должно сохраняться
            new UserTaskMessage('User task') // Должно сохраняться
        ];
        
        $conversation->method('getMessages')->willReturn($messages);
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator());
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressPreservesAllSystemAndUserTaskMessages(): void
    {
        $conversation = $this->createMock(Conversation::class);
        
        $messages = [
            new SystemMessage('System message 1'),
            new SystemMessage('System message 2'),
            new UserMessage('User message 1'), // Не должно сохраняться
            new UserMessage('User message 2'), // Не должно сохраняться
            new UserTaskMessage('User task 1'),
            new UserTaskMessage('User task 2'),
            new AssistantMessage('Assistant message', []) // Не должно сохраняться
        ];
        
        $conversation->method('getMessages')->willReturn($messages);
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator());
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressCreatesSliceMessageWithUniqueId(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getMessages')->willReturn([]);
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator());
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressHandlesSummaryGeneratorWithMultipleResults(): void
    {
        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getMessages')->willReturn([]);
        
        $summaryResults = [
            new ProcessingResult(
                completed: false,
                answer: 'Step 1',
                conversation: $conversation,
                contextFill: 0,
            ),
            new ProcessingResult(
                completed: false,
                answer: 'Step 2',
                conversation: $conversation,
                contextFill: 0,
            ),
            new ProcessingResult(
                completed: true,
                answer: 'Final summary',
                conversation: $conversation,
                contextFill: 0,
            )
        ];
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator($summaryResults));
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressWithMixedMessageTypes(): void
    {
        $conversation = $this->createMock(Conversation::class);
        
        $messages = [
            new SystemMessage('System'), // Должно сохраняться
            new UserMessage('User'), // Не должно сохраняться
            new UserTaskMessage('Task'), // Должно сохраняться
            new AssistantMessage('Assistant with content', []), // Не должно сохраняться
            new AssistantMessage('', []), // Не должно сохраняться
            new AssistantMessage('Another assistant', []) // Не должно сохраняться
        ];
        
        $conversation->method('getMessages')->willReturn($messages);
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator());
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    public function testCompressOnlyPreservesSystemAndUserTaskMessages(): void
    {
        $conversation = $this->createMock(Conversation::class);
        
        $messages = [
            new SystemMessage('System message 1'),
            new SystemMessage('System message 2'),
            new UserTaskMessage('User task 1'),
            new UserTaskMessage('User task 2'),
            new UserMessage('User message 1'), // Не должно сохраняться
            new UserMessage('User message 2'), // Не должно сохраняться
            new AssistantMessage('Assistant message 1', []), // Не должно сохраняться
            new AssistantMessage('Assistant message 2', []) // Не должно сохраняться
        ];
        
        $conversation->method('getMessages')->willReturn($messages);
        
        $this->summaryGenerator->method('generate')
            ->with($conversation)
            ->willReturn($this->createSummaryGenerator());
        
        $result = $this->compressor->compress($conversation);
        
        $this->assertInstanceOf(ConversationSlice::class, $result);
    }

    private function createSummaryGenerator(array $results = []): \Generator
    {
        foreach ($results as $result) {
            yield $result;
        }
    }
}