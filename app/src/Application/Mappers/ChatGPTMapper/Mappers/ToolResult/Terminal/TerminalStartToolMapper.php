<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Terminal;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Interface\ToolMapperInterface;
use Anymodule\Agentmodule\Application\Tools\Terminal\Start;
use Vasenin26\Conversation\Messages\ToolMessage;

class TerminalStartToolMapper implements ToolMapperInterface
{
    public function supports(ToolMessage $tool): bool
    {
        return $tool->name === Start::NAME;
    }

    public function map(ToolMessage $message): string
    {
        $result = json_decode($message->result, true);

        if (!$message->success) {
            return $result['message'] ?? 'Failed to start job';
        }

        $payload = $result['payload'] ?? [];
        $jobId = $payload['job_id'] ?? null;

        if (empty($jobId)) {
            return 'Job started, but no job_id was returned';
        }

        return "Started job {$jobId}. Use terminal-wait or terminal-peek with this job_id to check on it.";
    }
}
