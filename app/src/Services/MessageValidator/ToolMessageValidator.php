<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

class ToolMessageValidator
{
    public function getSupportedType(): string
    {
        return 'tool';
    }

    public function isValidContent(array $content): bool
    {
        return isset($content['id'], $content['name'], $content['result'])
            && is_string($content['id'])
            && is_string($content['name'])
            && is_string($content['result']);
    }

    public function getValidationErrors(array $content): array
    {
        $errors = [];
        if (!isset($content['id'])) {
            $errors[] = 'Missing required field: id for tool message';
        } elseif (!is_string($content['id'])) {
            $errors[] = "Field 'id' must be a string for tool message";
        }
        if (!isset($content['name'])) {
            $errors[] = 'Missing required field: name for tool message';
        } elseif (!is_string($content['name'])) {
            $errors[] = "Field 'name' must be a string for tool message";
        }
        if (!isset($content['result'])) {
            $errors[] = 'Missing required field: result for tool message';
        } elseif (!is_string($content['result'])) {
            $errors[] = "Field 'result' must be a string for tool message";
        }
        return $errors;
    }
}

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
