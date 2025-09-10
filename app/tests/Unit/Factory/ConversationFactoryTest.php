<?php

namespace Anymodule\Agentmodule\Tests\Unit\Factory;

use Anymodule\Agentmodule\Entity\Conversation\Messages\AssistantMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\SystemMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\ToolMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\UserMessage;
use Anymodule\Agentmodule\Factory\ConversationFactory;
use Anymodule\Agentmodule\Factory\MessageFactory;
use Anymodule\Agentmodule\Factory\MessageTypeValidatorFactory;
use Anymodule\Agentmodule\Services\MessageValidator\CompositeMessageValidator;
use PHPUnit\Framework\TestCase;

class ConversationFactoryTest extends TestCase
{
    private ConversationFactory $factory;
    
    protected function setUp(): void
    {
        $validator = new CompositeMessageValidator(new MessageTypeValidatorFactory());
        $messageFactory = new MessageFactory();
        $this->factory = new ConversationFactory($validator, $messageFactory);
    }
    
    public function testFromMessagesWithUserMessage(): void
    {
        $messages = [
            [
                'type' => 'user',
                'message' => [
                    'content' => 'Hello, world!'
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        $this->assertCount(1, $chatMessages);
        $this->assertInstanceOf(UserMessage::class, $chatMessages[0]);
        $this->assertEquals('user', $chatMessages[0]->getType());
        $this->assertEquals(['content' => 'Hello, world!'], $chatMessages[0]->getContent());
    }
    
    public function testFromMessagesWithSystemMessage(): void
    {
        $messages = [
            [
                'type' => 'system',
                'message' => [
                    'content' => 'You are a helpful assistant.'
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        $this->assertCount(1, $chatMessages);
        $this->assertInstanceOf(SystemMessage::class, $chatMessages[0]);
        $this->assertEquals('system', $chatMessages[0]->getType());
        $this->assertEquals(['content' => 'You are a helpful assistant.'], $chatMessages[0]->getContent());
    }
    
    public function testFromMessagesWithAssistantMessage(): void
    {
        $messages = [
            [
                'type' => 'assistant',
                'message' => [
                    'content' => 'Hello! How can I help you?',
                    'tool_calls' => [
                        ['id' => 'call_1', 'name' => 'get_weather', 'arguments' => '{"city": "Moscow"}']
                    ]
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        $this->assertCount(1, $chatMessages);
        $this->assertInstanceOf(AssistantMessage::class, $chatMessages[0]);
        $this->assertEquals('assistant', $chatMessages[0]->getType());
        $this->assertEquals([
            'content' => 'Hello! How can I help you?',
            'tool_calls' => [
                ['id' => 'call_1', 'name' => 'get_weather', 'arguments' => '{"city": "Moscow"}']
            ]
        ], $chatMessages[0]->getContent());
    }
    
    public function testFromMessagesWithToolMessage(): void
    {
        $messages = [
            [
                'type' => 'tool',
                'message' => [
                    'id' => 'call_1',
                    'name' => 'get_weather',
                    'result' => '{"temperature": 20, "condition": "sunny"}'
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        $this->assertCount(1, $chatMessages);
        $this->assertInstanceOf(ToolMessage::class, $chatMessages[0]);
        $this->assertEquals('tool', $chatMessages[0]->getType());
        $this->assertEquals([
            'id' => 'call_1',
            'name' => 'get_weather',
            'result' => '{"temperature": 20, "condition": "sunny"}'
        ], $chatMessages[0]->getContent());
    }
    
    public function testFromMessagesWithMultipleMessages(): void
    {
        $messages = [
            [
                'type' => 'system',
                'message' => [
                    'content' => 'You are a helpful assistant.'
                ]
            ],
            [
                'type' => 'user',
                'message' => [
                    'content' => 'What is the weather like?'
                ]
            ],
            [
                'type' => 'assistant',
                'message' => [
                    'content' => 'I can help you check the weather.',
                    'tool_calls' => [
                        ['id' => 'call_1', 'name' => 'get_weather', 'arguments' => '{"city": "Moscow"}']
                    ]
                ]
            ],
            [
                'type' => 'tool',
                'message' => [
                    'id' => 'call_1',
                    'name' => 'get_weather',
                    'result' => '{"temperature": 20, "condition": "sunny"}'
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        $this->assertCount(4, $chatMessages);
        $this->assertInstanceOf(SystemMessage::class, $chatMessages[0]);
        $this->assertInstanceOf(UserMessage::class, $chatMessages[1]);
        $this->assertInstanceOf(AssistantMessage::class, $chatMessages[2]);
        $this->assertInstanceOf(ToolMessage::class, $chatMessages[3]);
    }
    
    public function testFromMessagesWithMissingType(): void
    {
        $messages = [
            [
                'message' => [
                    'content' => 'Hello, world!'
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        // Невалидное сообщение должно быть пропущено
        $this->assertCount(0, $chatMessages);
    }
    
    public function testFromMessagesWithUnknownType(): void
    {
        $messages = [
            [
                'type' => 'unknown',
                'message' => [
                    'content' => 'Hello, world!'
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        // Невалидное сообщение должно быть пропущено
        $this->assertCount(0, $chatMessages);
    }
    
    public function testFromMessagesWithEmptyArray(): void
    {
        $chat = $this->factory->fromMessages([]);
        $chatMessages = $chat->getMessages();
        
        $this->assertCount(0, $chatMessages);
    }
    
    public function testFromMessagesWithInvalidMessages(): void
    {
        $messages = [
            [
                'type' => 'user',
                'message' => [
                    'content' => 'Valid user message'
                ]
            ],
            [
                'type' => 'user',
                'message' => [] // Невалидное сообщение - отсутствует content
            ],
            [
                'type' => 'system',
                'message' => [
                    'content' => 'Valid system message'
                ]
            ],
            [
                'type' => 'unknown',
                'message' => [
                    'content' => 'Unknown type message'
                ]
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        // Должны остаться только валидные сообщения
        $this->assertCount(2, $chatMessages);
        $this->assertInstanceOf(UserMessage::class, $chatMessages[0]);
        $this->assertInstanceOf(SystemMessage::class, $chatMessages[1]);
    }
    
    public function testFromMessagesWithMissingMessageField(): void
    {
        $messages = [
            [
                'type' => 'user',
                'message' => []
            ]
        ];
        
        $chat = $this->factory->fromMessages($messages);
        $chatMessages = $chat->getMessages();
        
        // Невалидное сообщение должно быть пропущено
        $this->assertCount(0, $chatMessages);
    }
}
