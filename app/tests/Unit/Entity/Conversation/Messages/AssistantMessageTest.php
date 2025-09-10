<?php

namespace Anymodule\Agentmodule\Tests\Unit\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Messages\AssistantMessage;
use PHPUnit\Framework\TestCase;

class AssistantMessageTest extends TestCase
{
    public function testCreateFromData(): void
    {
        $content = [
            'content' => 'Hello! How can I help you?',
            'tool_calls' => [
                ['id' => 'call_1', 'name' => 'get_weather', 'arguments' => '{"city": "Moscow"}']
            ]
        ];
        
        $message = AssistantMessage::createFromData($content);
        
        $this->assertInstanceOf(AssistantMessage::class, $message);
        $this->assertEquals('assistant', $message->getType());
        $this->assertEquals($content, $message->getContent());
    }
    
    public function testCreateFromDataWithoutToolCalls(): void
    {
        $content = ['content' => 'Hello! How can I help you?'];
        
        $message = AssistantMessage::createFromData($content);
        
        $this->assertInstanceOf(AssistantMessage::class, $message);
        $this->assertEquals('assistant', $message->getType());
        $this->assertEquals([
            'content' => 'Hello! How can I help you?',
            'tool_calls' => []
        ], $message->getContent());
    }
    
    public function testGetType(): void
    {
        $message = new AssistantMessage('Hello!', []);
        
        $this->assertEquals('assistant', $message->getType());
    }
    
    public function testGetContent(): void
    {
        $content = 'Hello!';
        $toolCalls = [['id' => 'call_1', 'name' => 'get_weather']];
        $message = new AssistantMessage($content, $toolCalls);
        
        $this->assertEquals([
            'content' => $content,
            'tool_calls' => $toolCalls
        ], $message->getContent());
    }
}
