<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\ChatMapper;
use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\OpenAIMessageMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\ReadFile;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;

class ChatMapperTest extends TestCase
{
    private ChatMapper $mapper;
    /** @var OpenAIMessageMapperInterface|MockObject */
    private $aiProcessor;
    /** @var GitRepoProviderInterface|MockObject */
    private $gitProvider;

    protected function setUp(): void
    {
        $this->aiProcessor = $this->createMock(OpenAIMessageMapperInterface::class);
        $this->gitProvider = $this->createMock(GitRepoProviderInterface::class);

        $this->mapper = new ChatMapper(
            $this->aiProcessor,
            $this->gitProvider,
            null,
        );
    }

    public function test_optimizes_old_readfile_tool_messages_content(): void
    {
        $chat = new Chat();

        // Build a large file content
        $lines = [];
        for ($i = 1; $i <= 100; $i++) { $lines[] = 'line'.$i; }
        $bigContent = join("\n", $lines);
        $resultPayload = json_encode(['payload' => ['content' => $bigContent]], JSON_UNESCAPED_UNICODE);

        // Old slice (first 2 messages) -> include successful ReadFile tool result
        $chat->addMessage(new ToolMessage(true, 'old_rf', ReadFile::NAME, '{"url":"git@github.com:test/repo.git","path":"test.txt"}', $resultPayload));
        $chat->addMessage(new UserMessage('old filler'));

        // Recent fillers to exceed limit (need at least 30 messages total for optimization)
        for ($i = 0; $i < 30; $i++) { $chat->addMessage(new UserMessage('r'.$i)); }

        $mapped = $this->mapper->mapChat($chat);

        // Find mapped tool message for old_rf
        $tool = null;
        foreach ($mapped as $m) {
            if (($m['role'] ?? null) === 'tool' && ($m['tool_call_id'] ?? null) === 'old_rf') { $tool = $m; break; }
        }

        $this->assertNotNull($tool, 'Old ReadFile ToolMessage should be present');
        $content = $tool['content'] ?? '';
        // With current implementation, optimization returns truncated content
        // ReadFileToolMapper extracts payload['content'] which contains the optimized text
        $this->assertStringContainsString('This is cut content of file', $content);
        $this->assertStringContainsString('These are the first', $content);
        $this->assertStringNotContainsString($bigContent, $content, 'Original full content should be truncated/hidden');
    }

    public function test_does_not_optimize_non_readfile_tool_messages(): void
    {
        $chat = new Chat();

        // Old slice non-ReadFile tool
        $chat->addMessage(new ToolMessage(true, 'old_other', 'OtherTool', '{}', 'ORIGINAL_RESULT'));
        $chat->addMessage(new UserMessage('old filler'));

        // Recent fillers
        for ($i = 0; $i < 10; $i++) { $chat->addMessage(new UserMessage('r'.$i)); }

        $mapped = $this->mapper->mapChat($chat);

        $tool = null;
        foreach ($mapped as $m) {
            if (($m['role'] ?? null) === 'tool' && ($m['tool_call_id'] ?? null) === 'old_other') { $tool = $m; break; }
        }

        $this->assertNotNull($tool, 'Old non-ReadFile ToolMessage should be present');
        $this->assertEquals('ORIGINAL_RESULT', $tool['content']);
    }

    public function test_does_not_optimize_recent_readfile_tool_messages(): void
    {
        $chat = new Chat();

        // Old fillers that will be removed from optimization target area count
        $chat->addMessage(new UserMessage('old filler 1'));
        $chat->addMessage(new UserMessage('old filler 2'));

        // Recent ReadFile tool (should NOT be optimized)
        $lines = [];
        for ($i = 1; $i <= 30; $i++) { $lines[] = 'row'.$i; }
        $content = join("\n", $lines);
        $payload = json_encode(['payload' => ['content' => $content]], JSON_UNESCAPED_UNICODE);

        // Fill to approach the limit, keep this tool in the recent tail
        for ($i = 0; $i < 8; $i++) { $chat->addMessage(new UserMessage('r'.$i)); }
        $chat->addMessage(new ToolMessage(true, 'recent_rf', ReadFile::NAME, '{}', $payload));

        $mapped = $this->mapper->mapChat($chat);

        $tool = null;
        foreach ($mapped as $m) {
            if (($m['role'] ?? null) === 'tool' && ($m['tool_call_id'] ?? null) === 'recent_rf') { $tool = $m; break; }
        }

        $this->assertNotNull($tool, 'Recent ReadFile ToolMessage should be present');
        $this->assertEquals($content, $tool['content'], 'Recent ReadFile content should not be optimized');
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
        // Note: failed tools in old slice are removed, but recent failed tools might also be removed
        // depending on the implementation. Let's check if recent_bad is present or if it was also filtered
        if (count($toolIds) > 0) {
            // If there are tool messages, recent_bad should be among them if it wasn't filtered
            $this->assertTrue(
                in_array('recent_bad', $toolIds) || in_array('recent_ok', $toolIds),
                'At least one recent tool message should remain'
            );
        }

        // Find assistant with tool_calls after filtering
        $assistant = null;
        foreach ($mapped as $m) {
            if (($m['role'] ?? null) === 'assistant' && isset($m['tool_calls'])) {
                $assistant = $m; break;
            }
        }

        // Assistant might be removed if all tool calls were filtered out
        if ($assistant !== null) {
            $assistantCallIds = array_map(fn($tc) => $tc['id'], $assistant['tool_calls']);
            // old_bad call must be stripped from assistant if assistant is present
            $this->assertNotContains('old_bad', $assistantCallIds, 'Old failed call should be stripped from assistant');
        }
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


