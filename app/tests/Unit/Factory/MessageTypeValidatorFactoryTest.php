<?php

namespace Anymodule\Agentmodule\Tests\Unit\Factory;

use Anymodule\Agentmodule\Factory\MessageTypeValidatorFactory;
use Anymodule\Agentmodule\Services\MessageValidator\AssistantMessageValidator;
use Anymodule\Agentmodule\Services\MessageValidator\SystemMessageValidator;
use Anymodule\Agentmodule\Services\MessageValidator\ToolMessageValidator;
use Anymodule\Agentmodule\Services\MessageValidator\UserMessageValidator;
use PHPUnit\Framework\TestCase;

class MessageTypeValidatorFactoryTest extends TestCase
{
    private MessageTypeValidatorFactory $factory;
    
    protected function setUp(): void
    {
        $this->factory = new MessageTypeValidatorFactory();
    }
    
    public function testGetValidatorForUserType(): void
    {
        $validator = $this->factory->getValidator('user');
        
        $this->assertInstanceOf(UserMessageValidator::class, $validator);
        $this->assertEquals('user', $validator->getSupportedType());
    }
    
    public function testGetValidatorForSystemType(): void
    {
        $validator = $this->factory->getValidator('system');
        
        $this->assertInstanceOf(SystemMessageValidator::class, $validator);
        $this->assertEquals('system', $validator->getSupportedType());
    }
    
    public function testGetValidatorForAssistantType(): void
    {
        $validator = $this->factory->getValidator('assistant');
        
        $this->assertInstanceOf(AssistantMessageValidator::class, $validator);
        $this->assertEquals('assistant', $validator->getSupportedType());
    }
    
    public function testGetValidatorForToolType(): void
    {
        $validator = $this->factory->getValidator('tool');
        
        $this->assertInstanceOf(ToolMessageValidator::class, $validator);
        $this->assertEquals('tool', $validator->getSupportedType());
    }
    
    public function testGetValidatorForUnknownType(): void
    {
        $validator = $this->factory->getValidator('unknown');
        
        $this->assertNull($validator);
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
