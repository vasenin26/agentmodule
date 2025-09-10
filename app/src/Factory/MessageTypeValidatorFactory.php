<?php

namespace Anymodule\Agentmodule\Factory;

use Anymodule\Agentmodule\Interface\MessageTypeValidatorFactoryInterface;
use Anymodule\Agentmodule\Interface\MessageTypeValidatorInterface;
use Anymodule\Agentmodule\Services\MessageValidator\AssistantMessageValidator;
use Anymodule\Agentmodule\Services\MessageValidator\SystemMessageValidator;
use Anymodule\Agentmodule\Services\MessageValidator\ToolMessageValidator;
use Anymodule\Agentmodule\Services\MessageValidator\UserMessageValidator;

class MessageTypeValidatorFactory implements MessageTypeValidatorFactoryInterface
{
    private array $validators = [];
    
    public function __construct()
    {
        $this->validators = [
            'user' => new UserMessageValidator(),
            'system' => new SystemMessageValidator(),
            'assistant' => new AssistantMessageValidator(),
            'tool' => new ToolMessageValidator(),
        ];
    }
    
    public function getValidator(string $messageType): ?MessageTypeValidatorInterface
    {
        return $this->validators[$messageType] ?? null;
    }
    
    public function getSupportedTypes(): array
    {
        return array_keys($this->validators);
    }
}
