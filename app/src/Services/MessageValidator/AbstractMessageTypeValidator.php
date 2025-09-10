<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

use Anymodule\Agentmodule\Interface\MessageTypeValidatorInterface;

abstract class AbstractMessageTypeValidator implements MessageTypeValidatorInterface
{
    protected function validateRequiredStringField(array $content, string $fieldName, string $messageType): array
    {
        $errors = [];
        
        if (!isset($content[$fieldName])) {
            $errors[] = "Missing required field: {$fieldName} for {$messageType} message";
        } elseif (!is_string($content[$fieldName])) {
            $errors[] = "Field '{$fieldName}' must be a string for {$messageType} message";
        }
        
        return $errors;
    }
    
    protected function validateOptionalArrayField(array $content, string $fieldName, string $messageType): array
    {
        $errors = [];
        
        if (isset($content[$fieldName]) && !is_array($content[$fieldName])) {
            $errors[] = "Field '{$fieldName}' must be an array for {$messageType} message";
        }
        
        return $errors;
    }
}
