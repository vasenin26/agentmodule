<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

class SystemMessageValidator
{
    public function getSupportedType(): string
    {
        return 'system';
    }

    public function isValidContent(array $content): bool
    {
        return isset($content['content']) && is_string($content['content']);
    }

    public function getValidationErrors(array $content): array
    {
        $errors = [];
        if (!isset($content['content'])) {
            $errors[] = 'Missing required field: content for system message';
            return $errors;
        }
        if (!is_string($content['content'])) {
            $errors[] = "Field 'content' must be a string for system message";
        }
        return $errors;
    }
}

<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

class SystemMessageValidator extends AbstractMessageTypeValidator
{
    public function getSupportedType(): string
    {
        return 'system';
    }
    
    public function isValidContent(array $content): bool
    {
        return isset($content['content']) && is_string($content['content']);
    }
    
    public function getValidationErrors(array $content): array
    {
        return $this->validateRequiredStringField($content, 'content', 'system');
    }
}
