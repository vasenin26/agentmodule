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
