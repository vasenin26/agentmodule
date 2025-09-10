<?php

namespace Anymodule\Agentmodule\Tests\Unit\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Messages\SystemMessage;
use PHPUnit\Framework\TestCase;

class SystemMessageTest extends TestCase
{
    public function testCreateFromData(): void
    {
        $content = ['content' => 'You are a helpful assistant.'];
        
        $message = SystemMessage::createFromData($content);
        
        $this->assertInstanceOf(SystemMessage::class, $message);
        $this->assertEquals('system', $message->getType());
        $this->assertEquals($content, $message->getContent());
    }
    
    public function testGetType(): void
    {
        $message = new SystemMessage('You are a helpful assistant.');
        
        $this->assertEquals('system', $message->getType());
    }
    
    public function testGetContent(): void
    {
        $content = 'You are a helpful assistant.';
        $message = new SystemMessage($content);
        
        $this->assertEquals(['content' => $content], $message->getContent());
    }
}
