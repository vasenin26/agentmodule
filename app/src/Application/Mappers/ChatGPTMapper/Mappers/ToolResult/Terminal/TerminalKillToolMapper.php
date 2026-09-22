<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Terminal;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Terminal\Kill;
use Vasenin26\Conversation\Messages\ToolMessage;

class TerminalKillToolMapper implements ToolMapperInterface
{
    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === Kill::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if (!$message->success) {
            return $result['message'] ?? 'Failed to kill job';
        }

        $payload = $result['payload'] ?? [];
        $status = $payload['status'] ?? 'unknown';
        $exitCode = $payload['exit_code'] ?? null;

        $output = "Job {$payload['job_id']}: {$status}";
        if ($exitCode !== null) {
            $output .= " (exit_code: {$exitCode})";
        }

        return $output;
    }
}
