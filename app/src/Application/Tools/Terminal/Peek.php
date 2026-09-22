<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class Peek implements ToolInterface
{
    use TerminalPollResponseTrait;

    const NAME = 'terminal-peek';

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

        $request = [
            'action' => 'peek',
            'job_id' => $jobId,
            'stdout_offset' => isset($args['stdout_offset']) ? (int)$args['stdout_offset'] : 0,
            'stderr_offset' => isset($args['stderr_offset']) ? (int)$args['stderr_offset'] : 0,
        ];

        try {
            $response = $this->client->send($request, 5);
        } catch (CommandProxyException $e) {
            Log::exception($e, 'Terminal Peek execution error', ['job_id' => $jobId]);
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
                'description' => 'Look at a background job (started via terminal-start) without waiting and '
                    . 'without killing it: returns its current status and any new output since the given '
                    . 'offsets immediately.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'job_id' => [
                            'type' => 'string',
                            'description' => 'Job id returned by terminal-start (required).',
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
