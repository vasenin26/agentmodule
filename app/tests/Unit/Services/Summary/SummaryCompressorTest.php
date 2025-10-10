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
}