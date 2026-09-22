<?php

namespace Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\ToolResult\Terminal;

/**
 * Shared rendering for terminal-wait/terminal-peek payloads, which both carry the same
 * {status, exit_code, stdout_delta, stderr_delta, ...} shape (see TerminalPollResponseTrait).
 */
trait TerminalPollToolMapperTrait
{
    private function mapPollPayload(array $payload): string
    {
        $status = $payload['status'] ?? 'unknown';
        $exitCode = $payload['exit_code'] ?? null;
        $stdoutDelta = $payload['stdout_delta'] ?? '';
        $stderrDelta = $payload['stderr_delta'] ?? '';

        $output = "Job status: {$status}";
        if ($exitCode !== null) {
            $output .= ", exit_code: {$exitCode}";
        }

        if ($stdoutDelta !== '') {
            $output .= "\n\nSTDOUT:\n```\n{$stdoutDelta}\n```";
        }
        if ($stderrDelta !== '') {
            $output .= "\n\nSTDERR:\n```\n{$stderrDelta}\n```";
        }
        if ($stdoutDelta === '' && $stderrDelta === '') {
            $output .= "\n\n(no new output)";
        }
        if (!empty($payload['truncated'])) {
            $output .= "\n\n(output was truncated by the proxy — offsets have advanced past the discarded data)";
        }

        $output .= "\n\nNext offsets — stdout_offset: " . ($payload['stdout_offset'] ?? 0)
            . ', stderr_offset: ' . ($payload['stderr_offset'] ?? 0);

        return $output;
    }
}
