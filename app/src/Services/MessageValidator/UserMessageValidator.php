<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

class UserMessageValidator
{
    public function getSupportedType(): string
    {
        return 'user';
    }

    public function isValidContent(array $content): bool
    {
        if (!isset($content['content'])) {
            return false;
        }
        return is_string($content['content']);
    }

    public function getValidationErrors(array $content): array
    {
        $errors = [];
        if (!isset($content['content'])) {
            $errors[] = 'Missing required field: content for user message';
            return $errors;
        }
        if (!is_string($content['content'])) {
            $errors[] = "Field 'content' must be a string for user message";
        }
        return $errors;
    }
}

<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

class UserMessageValidator extends AbstractMessageTypeValidator
{
    public function getSupportedType(): string
    {
        return 'user';
    }
    
    public function isValidContent(array $content): bool
    {
        return isset($content['content']) && is_string($content['content']);
    }
    
    public function getValidationErrors(array $content): array
    {
        return $this->validateRequiredStringField($content, 'content', 'user');
    }
}
