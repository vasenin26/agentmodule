<?php

namespace Anymodule\Agentmodule\Tests\Unit\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Messages\ToolMessage;
use PHPUnit\Framework\TestCase;

class ToolMessageTest extends TestCase
{
    public function testCreateFromData(): void
    {
        $content = [
            'id' => 'call_1',
            'name' => 'get_weather',
            'result' => '{"temperature": 20, "condition": "sunny"}'
        ];
        
        $message = ToolMessage::createFromData($content);
        
        $this->assertInstanceOf(ToolMessage::class, $message);
        $this->assertEquals('tool', $message->getType());
        $this->assertEquals($content, $message->getContent());
    }
    
    public function testGetType(): void
    {
        $message = new ToolMessage('call_1', 'get_weather', '{"temperature": 20}');
        
        $this->assertEquals('tool', $message->getType());
    }
    
    public function testGetContent(): void
    {
        $id = 'call_1';
        $name = 'get_weather';
        $result = '{"temperature": 20}';
        $message = new ToolMessage($id, $name, $result);
        
        $this->assertEquals([
            'id' => $id,
            'name' => $name,
            'result' => $result
        ], $message->getContent());
    }
}
