<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\MessageValidator;

use Anymodule\Agentmodule\Factory\MessageTypeValidatorFactory;
use Anymodule\Agentmodule\Services\MessageValidator\CompositeMessageValidator;
use PHPUnit\Framework\TestCase;

class CompositeMessageValidatorTest extends TestCase
{
    private CompositeMessageValidator $validator;
    
    protected function setUp(): void
    {
        $this->validator = new CompositeMessageValidator(new MessageTypeValidatorFactory());
    }
    
    public function testValidUserMessage(): void
    {
        $messageData = [
            'type' => 'user',
            'message' => [
                'content' => 'Hello, world!'
            ]
        ];
        
        $this->assertTrue($this->validator->isValidMessage($messageData));
        $this->assertEmpty($this->validator->getValidationErrors($messageData));
    }
    
    public function testValidSystemMessage(): void
    {
        $messageData = [
            'type' => 'system',
            'message' => [
                'content' => 'You are a helpful assistant.'
            ]
        ];
        
        $this->assertTrue($this->validator->isValidMessage($messageData));
        $this->assertEmpty($this->validator->getValidationErrors($messageData));
    }
    
    public function testValidAssistantMessage(): void
    {
        $messageData = [
            'type' => 'assistant',
            'message' => [
                'content' => 'Hello! How can I help you?',
                'tool_calls' => [
                    ['id' => 'call_1', 'name' => 'get_weather', 'arguments' => '{"city": "Moscow"}']
                ]
            ]
        ];
        
        $this->assertTrue($this->validator->isValidMessage($messageData));
        $this->assertEmpty($this->validator->getValidationErrors($messageData));
    }
    
    public function testValidAssistantMessageWithoutToolCalls(): void
    {
        $messageData = [
            'type' => 'assistant',
            'message' => [
                'content' => 'Hello! How can I help you?'
            ]
        ];
        
        $this->assertTrue($this->validator->isValidMessage($messageData));
        $this->assertEmpty($this->validator->getValidationErrors($messageData));
    }
    
    public function testValidToolMessage(): void
    {
        $messageData = [
            'type' => 'tool',
            'message' => [
                'id' => 'call_1',
                'name' => 'get_weather',
                'result' => '{"temperature": 20, "condition": "sunny"}'
            ]
        ];
        
        $this->assertTrue($this->validator->isValidMessage($messageData));
        $this->assertEmpty($this->validator->getValidationErrors($messageData));
    }
    
    public function testInvalidMessageMissingType(): void
    {
        $messageData = [
            'message' => [
                'content' => 'Hello, world!'
            ]
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains('Missing required field: type', $errors);
    }
    
    public function testInvalidMessageMissingMessage(): void
    {
        $messageData = [
            'type' => 'user'
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains('Missing required field: message', $errors);
    }
    
    public function testInvalidMessageMessageNotArray(): void
    {
        $messageData = [
            'type' => 'user',
            'message' => 'not an array'
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains('Field "message" must be an array', $errors);
    }
    
    public function testInvalidUserMessageMissingContent(): void
    {
        $messageData = [
            'type' => 'user',
            'message' => []
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains('Missing required field: content for user message', $errors);
    }
    
    public function testInvalidUserMessageContentNotString(): void
    {
        $messageData = [
            'type' => 'user',
            'message' => [
                'content' => 123
            ]
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains("Field 'content' must be a string for user message", $errors);
    }
    
    public function testInvalidAssistantMessageToolCallsNotArray(): void
    {
        $messageData = [
            'type' => 'assistant',
            'message' => [
                'content' => 'Hello!',
                'tool_calls' => 'not an array'
            ]
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains("Field 'tool_calls' must be an array for assistant message", $errors);
    }
    
    public function testInvalidToolMessageMissingId(): void
    {
        $messageData = [
            'type' => 'tool',
            'message' => [
                'name' => 'get_weather',
                'result' => '{"temperature": 20}'
            ]
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains('Missing required field: id for tool message', $errors);
    }
    
    public function testUnknownMessageType(): void
    {
        $messageData = [
            'type' => 'unknown',
            'message' => [
                'content' => 'Hello, world!'
            ]
        ];
        
        $this->assertFalse($this->validator->isValidMessage($messageData));
        $errors = $this->validator->getValidationErrors($messageData);
        $this->assertContains('Unknown message type: unknown', $errors);
    }
}
