<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Entity\ToolResult;

/**
 * Shared response handling for terminal-wait/terminal-peek: both send a job_id + offsets
 * and get back the same {status, exit_code, *_delta, *_offset} shape (docs/command-proxy-protocol.md).
 *
 * `message` here is a short summary (used for logging/fallback only) — the full stdout/stderr
 * rendering shown to the model lives in TerminalWaitToolMapper/TerminalPeekToolMapper, which
 * read the structured payload below.
 */
trait TerminalPollResponseTrait
{
    private function toolResultFromPollResponse(array $response): ToolResult
    {
        if (isset($response['error'])) {
            return new ToolResult(false, $response['message'] ?? $response['error']);
        }

        $status = $response['status'] ?? 'running';
        $exitCode = $response['exit_code'] ?? null;
        $stdoutDelta = (string)($response['stdout_delta'] ?? '');
        $stderrDelta = (string)($response['stderr_delta'] ?? '');

        $message = "Job status: {$status}" . ($exitCode !== null ? ", exit_code: {$exitCode}" : '');

        return new ToolResult(true, $message, [
            'job_id' => $response['job_id'] ?? null,
            'status' => $status,
            'exit_code' => $exitCode,
            'stdout_delta' => $stdoutDelta,
            'stdout_offset' => $response['stdout_offset'] ?? null,
            'stderr_delta' => $stderrDelta,
            'stderr_offset' => $response['stderr_offset'] ?? null,
            'truncated' => (bool)($response['truncated'] ?? false),
        ]);
    }
}
