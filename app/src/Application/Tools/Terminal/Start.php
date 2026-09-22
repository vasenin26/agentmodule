<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Logger\Log;
use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class Start implements ToolInterface
{
    const NAME = 'terminal-start';

    private const DEFAULT_MAX_LIFETIME = 600;

    public function __construct(
        private ?CommandProxyClientInterface $client = null,
    ) {
        $this->client ??= new CommandProxyClient();
    }

    public function execute(array $args): ?ToolResult
    {
        if (!array_key_exists('command', $args)) {
            return null; // semantic: no result if command not provided
        }

        $command = (string)($args['command'] ?? '');
        if ($command === '') {
            return new ToolResult(false, 'Empty command provided');
        }

        $request = [
            'action' => 'start',
            'command' => $command,
            'max_lifetime' => isset($args['max_lifetime']) ? (int)$args['max_lifetime'] : self::DEFAULT_MAX_LIFETIME,
        ];

        if (isset($args['cwd'])) {
            $request['cwd'] = (string)$args['cwd'];
        }
        if (isset($args['env']) && is_array($args['env'])) {
            $request['env'] = $args['env'];
        }

        try {
            $response = $this->client->send($request, 5);
        } catch (CommandProxyException $e) {
            Log::exception($e, 'Terminal Start execution error', ['command' => $command]);
            return new ToolResult(false, $e->getMessage());
        }

        if (isset($response['error'])) {
            return new ToolResult(false, $response['message'] ?? $response['error']);
        }

        $jobId = $response['job_id'] ?? null;
        if (!$jobId) {
            return new ToolResult(false, 'Proxy did not return a job_id');
        }

        return new ToolResult(true, "Started job {$jobId}", ['job_id' => $jobId]);
    }

    public function getProps(): array
    {
        return [
            'type' => 'function',
            'function' => [
                'name' => $this->getName(),
                'description' => 'Start a shell command in the background and return immediately with a job_id. '
                    . 'Use terminal-wait/terminal-peek to check on it and terminal-kill to stop it.',
                'parameters' => [
                    'type' => 'object',
                    'properties' => [
                        'command' => [
                            'type' => 'string',
                            'description' => 'Command to execute (required).',
                        ],
                        'cwd' => [
                            'type' => 'string',
                            'description' => 'Working directory for the command.',
                        ],
                        'env' => [
                            'type' => 'object',
                            'description' => 'Environment variables to pass to the process.',
                        ],
                        'max_lifetime' => [
                            'type' => 'integer',
                            'description' => 'Max seconds the proxy will let this job run before force-killing it (default: 600).',
                        ],
                    ],
                    'required' => ['command'],
                ],
            ],
        ];
    }

    public function getName(): string
    {
        return self::NAME;
    }
}
