<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class Wait implements ToolInterface
{
    use TerminalPollResponseTrait;

    const NAME = 'terminal-wait';

    private const DEFAULT_TIMEOUT = 15;

    // Protocol hard cap (docs/command-proxy-protocol.md): the client must never ask
    // the proxy to block longer than this, and must clamp read timeout to match.
    private const MAX_TIMEOUT = 30;

    public function __construct(
        private ?CommandProxyClientInterface $client = null,
    ) {
        $this->client ??= new CommandProxyClient();
    }

    public function execute(array $args): ?ToolResult
    {
        if (!array_key_exists('job_id', $args)) {
            return null; // semantic: no result if job_id not provided
        }

        $jobId = (string)($args['job_id'] ?? '');
        if ($jobId === '') {
            return new ToolResult(false, 'Empty job_id provided');
        }

        $timeout = isset($args['timeout']) ? (int)$args['timeout'] : self::DEFAULT_TIMEOUT;
        $timeout = max(1, min($timeout, self::MAX_TIMEOUT));

        $request = [
            'action' => 'wait',
            'job_id' => $jobId,
            'timeout' => $timeout,
            'stdout_offset' => isset($args['stdout_offset']) ? (int)$args['stdout_offset'] : 0,
            'stderr_offset' => isset($args['stderr_offset']) ? (int)$args['stderr_offset'] : 0,
        ];

        try {
            // Give the socket a bit more room than the proxy-side wait timeout for network/processing overhead.
            $response = $this->client->send($request, $timeout + 5);
        } catch (CommandProxyException $e) {
            Log::exception($e, 'Terminal Wait execution error', ['job_id' => $jobId]);
            return new ToolResult(false, $e->getMessage());
        }

        return $this->toolResultFromPollResponse($response);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Wait for a background job (started via terminal-start) to make progress, '
                    . 'without killing it. Blocks up to `timeout` seconds (max ' . self::MAX_TIMEOUT . '), '
                    . 'then returns the job status and any new output since the given offsets, whether or '
                    . 'not the job has finished. Call again with the returned offsets to keep watching it.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'job_id' => [
                            'type' => 'string',
                            'description' => 'Job id returned by terminal-start (required).',
                        ],
                        'timeout' => [
                            'type' => 'integer',
                            'description' => 'Max seconds to wait (default: ' . self::DEFAULT_TIMEOUT . ', hard cap: ' . self::MAX_TIMEOUT . ').',
                        ],
                        'stdout_offset' => [
                            'type' => 'integer',
                            'description' => 'Byte offset to read stdout from (use the offset returned by the previous call).',
                        ],
                        'stderr_offset' => [
                            'type' => 'integer',
                            'description' => 'Byte offset to read stderr from (use the offset returned by the previous call).',
                        ],
                    ],
                    'required' => ['job_id'],
                ],
            ],
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
