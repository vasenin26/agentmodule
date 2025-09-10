<?php

namespace Anymodule\Agentmodule\Tests\Unit\Factory;

use Anymodule\Agentmodule\Entity\Conversation\Messages\AssistantMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\SystemMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\ToolMessage;
use Anymodule\Agentmodule\Entity\Conversation\Messages\UserMessage;
use Anymodule\Agentmodule\Factory\MessageFactory;
use PHPUnit\Framework\TestCase;

class MessageFactoryTest extends TestCase
{
    private MessageFactory $factory;
    
    protected function setUp(): void
    {
        $this->factory = new MessageFactory();
    }
    
    public function testCreateUserMessage(): void
    {
        $message = $this->factory->createMessage('user', ['content' => 'Hello, world!']);
        
        $this->assertInstanceOf(UserMessage::class, $message);
        $this->assertEquals('user', $message->getType());
        $this->assertEquals(['content' => 'Hello, world!'], $message->getContent());
    }
    
    public function testCreateSystemMessage(): void
    {
        $message = $this->factory->createMessage('system', ['content' => 'You are a helpful assistant.']);
        
        $this->assertInstanceOf(SystemMessage::class, $message);
        $this->assertEquals('system', $message->getType());
        $this->assertEquals(['content' => 'You are a helpful assistant.'], $message->getContent());
    }
    
    public function testCreateAssistantMessage(): void
    {
        $content = [
            'content' => 'Hello! How can I help you?',
            'tool_calls' => [
                ['id' => 'call_1', 'name' => 'get_weather', 'arguments' => '{"city": "Moscow"}']
            ]
        ];
        
        $message = $this->factory->createMessage('assistant', $content);
        
        $this->assertInstanceOf(AssistantMessage::class, $message);
        $this->assertEquals('assistant', $message->getType());
        $this->assertEquals($content, $message->getContent());
    }
    
    public function testCreateAssistantMessageWithoutToolCalls(): void
    {
        $content = ['content' => 'Hello! How can I help you?'];
        
        $message = $this->factory->createMessage('assistant', $content);
        
        $this->assertInstanceOf(AssistantMessage::class, $message);
        $this->assertEquals('assistant', $message->getType());
        $this->assertEquals([
            'content' => 'Hello! How can I help you?',
            'tool_calls' => []
        ], $message->getContent());
    }
    
    public function testCreateToolMessage(): void
    {
        $content = [
            'id' => 'call_1',
            'name' => 'get_weather',
            'result' => '{"temperature": 20, "condition": "sunny"}'
        ];
        
        $message = $this->factory->createMessage('tool', $content);
        
        $this->assertInstanceOf(ToolMessage::class, $message);
        $this->assertEquals('tool', $message->getType());
        $this->assertEquals($content, $message->getContent());
    }
    
    public function testCreateMessageWithUnknownType(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Unknown message type: unknown');
        
        $this->factory->createMessage('unknown', ['content' => 'Hello, world!']);
    }
    
    public function testGetSupportedTypes(): void
    {
        $supportedTypes = $this->factory->getSupportedTypes();
        
        $this->assertCount(4, $supportedTypes);
        $this->assertContains('user', $supportedTypes);
        $this->assertContains('system', $supportedTypes);
        $this->assertContains('assistant', $supportedTypes);
        $this->assertContains('tool', $supportedTypes);
    }
}
