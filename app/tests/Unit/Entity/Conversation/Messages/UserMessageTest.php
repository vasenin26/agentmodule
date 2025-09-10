<?php

namespace Anymodule\Agentmodule\Tests\Unit\Entity\Conversation\Messages;

use Anymodule\Agentmodule\Entity\Conversation\Messages\UserMessage;
use PHPUnit\Framework\TestCase;

class UserMessageTest extends TestCase
{
    public function testCreateFromData(): void
    {
        $content = ['content' => 'Hello, world!'];
        
        $message = UserMessage::createFromData($content);
        
        $this->assertInstanceOf(UserMessage::class, $message);
        $this->assertEquals('user', $message->getType());
        $this->assertEquals($content, $message->getContent());
    }
    
    public function testGetType(): void
    {
        $message = new UserMessage('Hello, world!');
        
        $this->assertEquals('user', $message->getType());
    }
    
    public function testGetContent(): void
    {
        $content = 'Hello, world!';
        $message = new UserMessage($content);
        
        $this->assertEquals(['content' => $content], $message->getContent());
    }
}
