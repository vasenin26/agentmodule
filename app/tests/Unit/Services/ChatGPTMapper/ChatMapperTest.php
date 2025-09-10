<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Entity\Conversation\Chat;
use Anymodule\Agentmodule\Entity\Conversation\Message;
use Anymodule\Agentmodule\Entity\Conversation\Messages\AssistantMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\SystemMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\ToolMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\UserMessage;
use Anymodule\Agentmodule\Services\ChatGPTMapper\ChatMapper;
use OpenAI\Responses\Chat\CreateResponseMessage;
use OpenAI\Responses\Chat\CreateResponseToolCall;
use OpenAI\Responses\Chat\CreateResponseToolCallFunction;
use PHPUnit\Framework\TestCase;

class ChatMapperTest extends TestCase
{
    private ChatMapper $chatMapper;

    protected function setUp(): void
    {
        $this->chatMapper = new ChatMapper();
    }

    /**
     * Тест метода mapChat() с различными типами сообщений
     */
    public function testMapChatWithDifferentMessageTypes(): void
    {
        // Создаем чат с разными типами сообщений
        $chat = new Chat([
            new UserMessage('Привет!'),
            new SystemMessage('Ты помощник'),
            new AssistantMessage('Привет! Как дела?', [])
        ]);

        $result = $this->chatMapper->mapChat($chat);

        $this->assertIsArray($result);
        $this->assertCount(3, $result);

        // Проверяем первое сообщение (UserMessage)
        $this->assertEquals('user', $result[0]['role']);
        $this->assertEquals('Привет!', $result[0]['content']);

        // Проверяем второе сообщение (SystemMessage)
        $this->assertEquals('system', $result[1]['role']);
        $this->assertEquals('Ты помощник', $result[1]['content']);

        // Проверяем третье сообщение (AssistantMessage)
        $this->assertEquals('assistant', $result[2]['role']);
        $this->assertEquals('Привет! Как дела?', $result[2]['content']);
    }

    /**
     * Тест метода mapChat() с пустым чатом
     */
    public function testMapChatWithEmptyChat(): void
    {
        $chat = new Chat([]);
        $result = $this->chatMapper->mapChat($chat);

        $this->assertIsArray($result);
        $this->assertEmpty($result);
    }

    /**
     * Тест метода mapChat() с AssistantMessage содержащим tool calls
     */
    public function testMapChatWithAssistantMessageWithToolCalls(): void
    {
        $toolCalls = [
            [
                'id' => 'call_123',
                'type' => 'function',
                'function' => [
                    'name' => 'get_weather',
                    'arguments' => '{"city": "Moscow"}'
                ]
            ]
        ];

        $chat = new Chat([
            new AssistantMessage('Проверю погоду', $toolCalls)
        ]);

        $result = $this->chatMapper->mapChat($chat);

        $this->assertCount(1, $result);
        $this->assertEquals('assistant', $result[0]['role']);
        $this->assertEquals('Проверю погоду', $result[0]['content']);
        $this->assertEquals($toolCalls, $result[0]['tool_calls']);
    }

    /**
     * Тест метода prepareAssistantMessage() без tool calls
     */
    public function testPrepareAssistantMessageWithoutToolCalls(): void
    {
        $createResponseMessage = CreateResponseMessage::from([
            'role' => 'assistant',
            'content' => 'Привет! Как дела?',
            'tool_calls' => []
        ]);

        $result = $this->chatMapper->prepareAssistantMessage($createResponseMessage);

        $this->assertInstanceOf(AssistantMessage::class, $result);
        $this->assertEquals('Привет! Как дела?', $result->getContent()['content']);
        $this->assertEquals([], $result->getContent()['tool_calls']);
    }

    /**
     * Тест метода prepareAssistantMessage() с tool calls
     */
    public function testPrepareAssistantMessageWithToolCalls(): void
    {
        $createResponseMessage = CreateResponseMessage::from([
            'role' => 'assistant',
            'content' => 'Выполню запрос',
            'tool_calls' => [
                [
                    'id' => 'call_123',
                    'type' => 'function',
                    'function' => [
                        'name' => 'get_weather',
                        'arguments' => '{"city": "Moscow"}'
                    ]
                ],
                [
                    'id' => 'call_456',
                    'type' => 'function',
                    'function' => [
                        'name' => 'send_email',
                        'arguments' => '{"to": "user@example.com"}'
                    ]
                ]
            ]
        ]);

        $result = $this->chatMapper->prepareAssistantMessage($createResponseMessage);

        $this->assertInstanceOf(AssistantMessage::class, $result);
        $this->assertEquals('Выполню запрос', $result->getContent()['content']);

        $toolCallsArray = $result->getContent()['tool_calls'];
        $this->assertIsArray($toolCallsArray);
        $this->assertCount(2, $toolCallsArray);

        // Проверяем первый tool call
        $this->assertEquals('call_123', $toolCallsArray[0]['id']);
        $this->assertEquals('function', $toolCallsArray[0]['type']);
        $this->assertEquals('get_weather', $toolCallsArray[0]['function']['name']);
        $this->assertEquals('{"city": "Moscow"}', $toolCallsArray[0]['function']['arguments']);

        // Проверяем второй tool call
        $this->assertEquals('call_456', $toolCallsArray[1]['id']);
        $this->assertEquals('function', $toolCallsArray[1]['type']);
        $this->assertEquals('send_email', $toolCallsArray[1]['function']['name']);
        $this->assertEquals('{"to": "user@example.com"}', $toolCallsArray[1]['function']['arguments']);
    }

    /**
     * Тест метода mapToUserMessage()
     */
    public function testMapToUserMessage(): void
    {
        $content = 'Привет! Как дела?';
        $result = $this->chatMapper->mapToUserMessage($content);

        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertEquals($content, $result->content);
    }

    /**
     * Тест метода mapToUserMessage() с пустой строкой
     */
    public function testMapToUserMessageWithEmptyString(): void
    {
        $result = $this->chatMapper->mapToUserMessage('');

        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertEquals('', $result->content);
    }

    /**
     * Тест метода mapToUserMessage() с длинным текстом
     */
    public function testMapToUserMessageWithLongText(): void
    {
        $longText = str_repeat('Это очень длинный текст. ', 100);
        $result = $this->chatMapper->mapToUserMessage($longText);

        $this->assertInstanceOf(UserMessage::class, $result);
        $this->assertEquals($longText, $result->content);
    }

    /**
     * Тест метода mapToToolMessage()
     */
    public function testMapToToolMessage(): void
    {
        $id = 'call_123';
        $toolName = 'get_weather';
        $result = '{"temperature": 20, "condition": "sunny"}';

        $message = $this->chatMapper->mapToToolMessage($id, $toolName, $result);

        $this->assertInstanceOf(ToolMessage::class, $message);
        $this->assertEquals($id, $message->id);
        $this->assertEquals($toolName, $message->name);
        $this->assertEquals($result, $message->result);
    }

    /**
     * Тест метода mapToToolMessage() с пустыми параметрами
     */
    public function testMapToToolMessageWithEmptyParameters(): void
    {
        $message = $this->chatMapper->mapToToolMessage('', '', '');

        $this->assertInstanceOf(ToolMessage::class, $message);
        $this->assertEquals('', $message->id);
        $this->assertEquals('', $message->name);
        $this->assertEquals('', $message->result);
    }

    /**
     * Тест целостности данных при маппинге Chat
     */
    public function testDataIntegrityInMapChat(): void
    {
        $originalMessages = [
            new UserMessage('Привет!'),
            new SystemMessage('Ты помощник'),
            new AssistantMessage('Отлично!', [
                [
                    'id' => 'call_123',
                    'type' => 'function',
                    'function' => [
                        'name' => 'test_function',
                        'arguments' => '{"param": "value"}'
                    ]
                ]
            ])
        ];

        $chat = new Chat($originalMessages);
        $mappedMessages = $this->chatMapper->mapChat($chat);

        // Проверяем что количество сообщений сохранилось
        $this->assertCount(count($originalMessages), $mappedMessages);

        // Проверяем что данные не потерялись
        $this->assertEquals('Привет!', $mappedMessages[0]['content']);
        $this->assertEquals('Ты помощник', $mappedMessages[1]['content']);
        $this->assertEquals('Отлично!', $mappedMessages[2]['content']);
        $this->assertEquals('test_function', $mappedMessages[2]['tool_calls'][0]['function']['name']);
    }

    /**
     * Тест целостности данных при создании AssistantMessage
     */
    public function testDataIntegrityInPrepareAssistantMessage(): void
    {
        $originalToolCalls = [
            [
                'id' => 'call_123',
                'type' => 'function',
                'function' => [
                    'name' => 'get_weather',
                    'arguments' => '{"city": "Moscow", "units": "metric"}'
                ]
            ]
        ];

        $createResponseMessage = CreateResponseMessage::from([
            'role' => 'assistant',
            'content' => 'Получу данные о погоде',
            'tool_calls' => $originalToolCalls
        ]);

        $assistantMessage = $this->chatMapper->prepareAssistantMessage($createResponseMessage);

        // Проверяем что контент сохранился
        $this->assertEquals('Получу данные о погоде', $assistantMessage->getContent()['content']);

        // Проверяем что tool calls сохранились с правильной структурой
        $toolCallsArray = $assistantMessage->getContent()['tool_calls'];
        $this->assertCount(1, $toolCallsArray);
        $this->assertEquals('call_123', $toolCallsArray[0]['id']);
        $this->assertEquals('function', $toolCallsArray[0]['type']);
        $this->assertEquals('get_weather', $toolCallsArray[0]['function']['name']);
        $this->assertEquals('{"city": "Moscow", "units": "metric"}', $toolCallsArray[0]['function']['arguments']);
    }

    /**
     * Тест целостности данных при создании UserMessage
     */
    public function testDataIntegrityInMapToUserMessage(): void
    {
        $originalContent = 'Это тестовое сообщение с эмодзи 🚀 и специальными символами: !@#$%^&*()';
        $userMessage = $this->chatMapper->mapToUserMessage($originalContent);

        $this->assertEquals($originalContent, $userMessage->content);
    }

    /**
     * Тест целостности данных при создании ToolMessage
     */
    public function testDataIntegrityInMapToToolMessage(): void
    {
        $originalId = 'call_12345';
        $originalToolName = 'complex_function';
        $originalResult = '{"status": "success", "data": {"value": 42, "message": "Операция выполнена успешно"}}';

        $toolMessage = $this->chatMapper->mapToToolMessage($originalId, $originalToolName, $originalResult);

        $this->assertEquals($originalId, $toolMessage->id);
        $this->assertEquals($originalToolName, $toolMessage->name);
        $this->assertEquals($originalResult, $toolMessage->result);
    }
}