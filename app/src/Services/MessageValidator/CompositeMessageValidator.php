<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

use Anymodule\Agentmodule\Interface\MessageTypeValidatorFactoryInterface;
use Anymodule\Agentmodule\Interface\MessageValidatorInterface;

class CompositeMessageValidator implements MessageValidatorInterface
{
    private MessageTypeValidatorFactoryInterface $validatorFactory;
    
    public function __construct(MessageTypeValidatorFactoryInterface $validatorFactory)
    {
        $this->validatorFactory = $validatorFactory;
    }
    
    public function isValidMessage(array $messageData): bool
    {
        if (!isset($messageData['type']) || !isset($messageData['message'])) {
            return false;
        }
        
        $type = $messageData['type'];
        $content = $messageData['message'];
        
        if (!is_array($content)) {
            return false;
        }
        
        $validator = $this->validatorFactory->getValidator($type);
        if ($validator === null) {
            return false;
        }
        
        return $validator->isValidContent($content);
    }
    
    public function getValidationErrors(array $messageData): array
    {
        $errors = [];
        
        if (!isset($messageData['type'])) {
            $errors[] = 'Missing required field: type';
        }
        
        if (!isset($messageData['message'])) {
            $errors[] = 'Missing required field: message';
            return $errors;
        }
        
        $type = $messageData['type'] ?? '';
        $content = $messageData['message'];
        
        if (!is_array($content)) {
            $errors[] = 'Field "message" must be an array';
            return $errors;
        }
        
        $validator = $this->validatorFactory->getValidator($type);
        if ($validator === null) {
            $errors[] = "Unknown message type: {$type}";
            return $errors;
        }
        
        $errors = array_merge($errors, $validator->getValidationErrors($content));
        
        return $errors;
    }
}
