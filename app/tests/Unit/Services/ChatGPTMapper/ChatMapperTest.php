<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\OpenAIMessageProcessorInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;

class ChatMapperTest extends TestCase
{
    private ChatMapper $mapper;
    /** @var OpenAIMessageProcessorInterface|MockObject */
    private $aiProcessor;
    /** @var GitRepoProviderInterface|MockObject */
    private $gitProvider;

    protected function setUp(): void
    {
        $this->aiProcessor = $this->createMock(OpenAIMessageProcessorInterface::class);
        $this->gitProvider = $this->createMock(GitRepoProviderInterface::class);

        $this->mapper = new ChatMapper(
            $this->aiProcessor,
            $this->gitProvider,
            null,
        );
    }

    public function test_removes_failed_old_tool_results_and_related_calls_only_for_old_messages(): void
    {
        $chat = new Chat();

        // Total messages > LAST_MESSAGES_LIMIT (10). First 2 are considered "old".
        // Old failed tool result (should be removed everywhere)
        $chat->addMessage(new ToolMessage(false, 'old_bad', 'oldTool', '{}', 'Old failed'));
        // Any other old message
        $chat->addMessage(new UserMessage('old user'));

        // Assistant with two tool calls: one old_bad (should be stripped), one recent_bad (should remain)
        $assistantCalls = [
            ['id' => 'old_bad', 'name' => 'oldTool', 'arguments' => '{}'],
            ['id' => 'recent_bad', 'name' => 'recentTool', 'arguments' => '{"a":1}'],
        ];
        $chat->addMessage(new AssistantMessage('', $assistantCalls));

        // Matching recent tool results stack (only recent_bad should stay; old_bad already in old slice)
        // Place only matching tool right after assistant to satisfy chatHasAllAnswers
        $chat->addMessage(new ToolMessage(false, 'recent_bad', 'recentTool', '{"a":1}', 'Recent failed'));
        // Insert a non-tool message to end the tool stack, then any other tool results
        $chat->addMessage(new UserMessage('break tools stack'));
        $chat->addMessage(new ToolMessage(true, 'recent_ok', 'recentOk', '{"x":1}', 'Recent ok'));

        // Fill up to keep last 10 messages recent
        $chat->addMessage(new UserMessage('u1'));
        $chat->addMessage(new UserMessage('u2'));
        $chat->addMessage(new UserMessage('u3'));
        $chat->addMessage(new UserMessage('u4'));
        $chat->addMessage(new UserMessage('u5'));
        // Make total messages 12 to ensure first 2 are considered old
        $chat->addMessage(new UserMessage('u6'));
        $chat->addMessage(new UserMessage('u7'));

        $mapped = $this->mapper->mapChat($chat);

        // Collect tool messages by id
        $toolMessages = array_values(array_filter($mapped, fn($m) => ($m['role'] ?? null) === 'tool'));
        $toolIds = array_map(fn($m) => $m['tool_call_id'] ?? null, $toolMessages);

        // Assert old failed tool message is removed, but recent remains
        $this->assertNotContains('old_bad', $toolIds, 'Old failed ToolMessage should be removed');
        $this->assertContains('recent_bad', $toolIds, 'Recent failed ToolMessage should remain');

        // Find assistant with tool_calls after filtering
        $assistant = null;
        foreach ($mapped as $m) {
            if (($m['role'] ?? null) === 'assistant' && isset($m['tool_calls'])) {
                $assistant = $m; break;
            }
        }

        $this->assertNotNull($assistant, 'Assistant with tool_calls should be present');
        $assistantCallIds = array_map(fn($tc) => $tc['id'], $assistant['tool_calls']);

        // old_bad call must be stripped from assistant, recent_bad must stay
        $this->assertNotContains('old_bad', $assistantCallIds, 'Old failed call should be stripped from assistant');
        $this->assertContains('recent_bad', $assistantCallIds, 'Recent failed call should remain on assistant');
    }

    public function test_removes_empty_assistant_if_only_old_failed_calls_and_no_content(): void
    {
        $chat = new Chat();

        // Old failed tool result
        $chat->addMessage(new ToolMessage(false, 'old_bad', 'oldTool', '{}', 'Old failed'));
        // Another old filler to exceed limit boundary later
        $chat->addMessage(new UserMessage('old user'));

        // Assistant referencing only old_bad with empty content -> should be removed after filtering
        $chat->addMessage(new AssistantMessage('', [
            ['id' => 'old_bad', 'name' => 'oldTool', 'arguments' => '{}'],
        ]));

        // Recent fillers to ensure total > 10 and to keep ordering valid
        for ($i = 0; $i < 9; $i++) {
            $chat->addMessage(new UserMessage('r'.$i));
        }

        $mapped = $this->mapper->mapChat($chat);

        // Ensure there is no assistant produced (it should be dropped)
        foreach ($mapped as $m) {
            $this->assertFalse(($m['role'] ?? null) === 'assistant' && isset($m['tool_calls']) && empty($m['content']), 'Empty assistant with only old failed calls must be removed');
        }
    }
}


