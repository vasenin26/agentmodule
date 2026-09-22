<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class Kill implements ToolInterface
{
    const NAME = 'terminal-kill';

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

        $signal = strtoupper((string)($args['signal'] ?? 'TERM'));
        if (!in_array($signal, ['TERM', 'KILL'], true)) {
            return new ToolResult(false, "Invalid signal '{$signal}', expected TERM or KILL");
        }

        $request = [
            'action' => 'kill',
            'job_id' => $jobId,
            'signal' => $signal,
        ];

        try {
            $response = $this->client->send($request, 5);
        } catch (CommandProxyException $e) {
            Log::exception($e, 'Terminal Kill execution error', ['job_id' => $jobId]);
            return new ToolResult(false, $e->getMessage());
        }

        if (isset($response['error'])) {
            return new ToolResult(false, $response['message'] ?? $response['error']);
        }

        $status = $response['status'] ?? 'unknown';
        $exitCode = $response['exit_code'] ?? null;

        return new ToolResult(true, "Job {$jobId}: {$status}", [
            'job_id' => $jobId,
            'status' => $status,
            'exit_code' => $exitCode,
        ]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Kill a background job started via terminal-start.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'job_id' => [
                            'type' => 'string',
                            'description' => 'Job id returned by terminal-start (required).',
                        ],
                        'signal' => [
                            'type' => 'string',
                            'description' => 'TERM (default, graceful) or KILL (forceful).',
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
