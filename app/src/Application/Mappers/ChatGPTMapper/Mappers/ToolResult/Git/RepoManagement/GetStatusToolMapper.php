<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Git\RepoManagement;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Git\RepoManagement\GetStatus;
use Vasenin26\Conversation\Messages\ToolMessage;

class GetStatusToolMapper implements ToolMapperInterface
{

    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === GetStatus::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if(!$message->success) return $result['message'] ?? 'Can\'t get status';

        $payload = $result['payload'] ?? null;

        if(empty($payload)) {
            return 'No status information found';
        }

        $output = '';

        // Добавляем информацию о файлах
        if (isset($payload['modified']) && !empty($payload['modified'])) {
            $output .= "Modified files:\n";
            foreach ($payload['modified'] as $file) {
                $output .= "  " . $file . "\n";
            }
        }

        if (isset($payload['staged']) && !empty($payload['staged'])) {
            $output .= "Staged files:\n";
            foreach ($payload['staged'] as $file) {
                $output .= "  " . $file . "\n";
            }
        }

        if (isset($payload['untracked']) && !empty($payload['untracked'])) {
            $output .= "Untracked files:\n";
            foreach ($payload['untracked'] as $file) {
                $output .= "  " . $file . "\n";
            }
        }

        if (isset($payload['deleted']) && !empty($payload['deleted'])) {
            $output .= "Deleted files:\n";
            foreach ($payload['deleted'] as $file) {
                $output .= "  " . $file . "\n";
            }
        }

        return trim($output) ?: 'No changes detected';
    }
}
