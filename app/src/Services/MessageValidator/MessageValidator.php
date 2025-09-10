<?php

namespace Anymodule\Agentmodule\Services\MessageValidator;

class MessageValidator
{
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
        
        return match ($type) {
            'user' => $this->isValidUserMessage($content),
            'system' => $this->isValidSystemMessage($content),
            'assistant' => $this->isValidAssistantMessage($content),
            'tool' => $this->isValidToolMessage($content),
            default => false
        };
    }
    
    private function isValidUserMessage(array $content): bool
    {
        return isset($content['content']) && is_string($content['content']);
    }
    
    private function isValidSystemMessage(array $content): bool
    {
        return isset($content['content']) && is_string($content['content']);
    }
    
    private function isValidAssistantMessage(array $content): bool
    {
        if (!isset($content['content']) || !is_string($content['content'])) {
            return false;
        }
        
        // tool_calls может отсутствовать или быть пустым массивом
        if (isset($content['tool_calls'])) {
            return is_array($content['tool_calls']);
        }
        
        return true;
    }
    
    private function isValidToolMessage(array $content): bool
    {
        return isset($content['id']) && is_string($content['id']) &&
               isset($content['name']) && is_string($content['name']) &&
               isset($content['result']) && is_string($content['result']);
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
        
        switch ($type) {
            case 'user':
            case 'system':
                if (!isset($content['content'])) {
                    $errors[] = "Missing required field: content for {$type} message";
                } elseif (!is_string($content['content'])) {
                    $errors[] = "Field 'content' must be a string for {$type} message";
                }
                break;
                
            case 'assistant':
                if (!isset($content['content'])) {
                    $errors[] = 'Missing required field: content for assistant message';
                } elseif (!is_string($content['content'])) {
                    $errors[] = "Field 'content' must be a string for assistant message";
                }
                
                if (isset($content['tool_calls']) && !is_array($content['tool_calls'])) {
                    $errors[] = "Field 'tool_calls' must be an array for assistant message";
                }
                break;
                
            case 'tool':
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
                break;
                
            default:
                $errors[] = "Unknown message type: {$type}";
        }
        
        return $errors;
    }
}
