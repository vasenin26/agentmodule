<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

class ToolMessageValidator extends AbstractMessageTypeValidator
{
    public function getSupportedType(): string
    {
        return 'tool';
    }
    
    public function isValidContent(array $content): bool
    {
        return isset($content['id']) && is_string($content['id']) &&
               isset($content['name']) && is_string($content['name']) &&
               isset($content['result']) && is_string($content['result']);
    }
    
    public function getValidationErrors(array $content): array
    {
        $errors = [];
        
        $errors = array_merge($errors, $this->validateRequiredStringField($content, 'id', 'tool'));
        $errors = array_merge($errors, $this->validateRequiredStringField($content, 'name', 'tool'));
        $errors = array_merge($errors, $this->validateRequiredStringField($content, 'result', 'tool'));
        
        return $errors;
    }
}
