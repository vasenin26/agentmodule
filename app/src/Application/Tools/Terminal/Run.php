<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;

class Run implements ToolInterface
{
	const NAME = 'terminal-run';

	public function execute(array $args): ?ToolResult
	{
		try {
			if (!array_key_exists('command', $args)) {
				return null; // semantic: no result if command not provided
			}

			$command = (string)($args['command'] ?? '');
			if ($command === '') {
				return new ToolResult(false, 'Empty command provided');
			}

			$cwd = $args['cwd'] ?? null;
			$timeout = isset($args['timeout']) ? (int)$args['timeout'] : 10;
			$maxOutput = isset($args['max_output']) ? (int)$args['max_output'] : 10000;
			$allowShell = isset($args['allow_shell']) ? (bool)$args['allow_shell'] : false;

			if (!$allowShell) {
				// basic safety: prevent common shell metacharacters when shell not explicitly allowed
				if (preg_match('/[;&|`$<>\n\r]/', $command)) {
					return new ToolResult(false, 'Shell metacharacters detected; set allow_shell=true to permit.');
				}
			}

			$descriptorspec = [
				0 => ['pipe', 'r'], // stdin
				1 => ['pipe', 'w'], // stdout
				2 => ['pipe', 'w'], // stderr
			];

			$cmd = ['/bin/bash', '-lc', $command];

			$start = microtime(true);
			$process = @proc_open($cmd, $descriptorspec, $pipes, $cwd ?: null, is_array($args['env']) ? $args['env'] : null);
			if (!is_resource($process)) {
				return new ToolResult(false, 'Unable to start process (proc_open failed).');
			}

			// close stdin immediately
			if (isset($pipes[0]) && is_resource($pipes[0])) {
				fclose($pipes[0]);
			}

			foreach ([1,2] as $i) {
				if (isset($pipes[$i]) && is_resource($pipes[$i])) {
					stream_set_blocking($pipes[$i], false);
				}
			}

			$stdout = '';
			$stderr = '';
			$exitCode = null;

			$endTime = microtime(true) + max(1, $timeout);

			while (true) {
				$now = microtime(true);
				if ($now > $endTime) {
					// timeout
					@proc_terminate($process);
					$status = proc_get_status($process);
					$exitCode = $status['exitcode'] ?? -1;
					$stderr .= "\nProcess killed after timeout {$timeout}s";
					break;
				}

				$read = [];
				if (isset($pipes[1]) && is_resource($pipes[1])) $read[] = $pipes[1];
				if (isset($pipes[2]) && is_resource($pipes[2])) $read[] = $pipes[2];

				if (count($read) > 0) {
					$write = null;
					$except = null;
					$changed = @stream_select($read, $write, $except, 0, 200000); // 200ms
					if ($changed === false) {
						// stream_select failed, break to avoid infinite loop
						break;
					}

					foreach ($read as $stream) {
						$data = stream_get_contents($stream);
						if ($data === false || $data === '') continue;

						if ($stream === $pipes[1]) {
							$stdout .= $data;
							if (strlen($stdout) > $maxOutput) {
								$stdout = substr($stdout, 0, $maxOutput) . "\n...[truncated]";
							}
						} else {
							$stderr .= $data;
							if (strlen($stderr) > $maxOutput) {
								$stderr = substr($stderr, 0, $maxOutput) . "\n...[truncated]";
							}
						}
					}
				}

				$status = proc_get_status($process);
				if (!$status['running']) {
					$exitCode = $status['exitcode'];
					// read remaining
					if (isset($pipes[1]) && is_resource($pipes[1])) $stdout .= stream_get_contents($pipes[1]);
					if (isset($pipes[2]) && is_resource($pipes[2])) $stderr .= stream_get_contents($pipes[2]);
					break;
				}

				usleep(100000); // 100ms
			}

			// close pipes
			foreach ([1,2] as $i) {
				if (isset($pipes[$i]) && is_resource($pipes[$i])) {
					fclose($pipes[$i]);
				}
			}

			@proc_close($process);
			$elapsed = microtime(true) - $start;

			$payload = [
				'command' => $command,
				'cwd' => $cwd ?? getcwd(),
				'exit_code' => $exitCode,
				'stdout' => $stdout,
				'stderr' => $stderr,
				'elapsed' => round($elapsed, 3),
			];

			return new ToolResult(true, 'Command executed', $payload);
		} catch (\Throwable $e) {
			return new ToolResult(false, $e->getMessage(), ['exception' => get_class($e)]);
		}
	}

	public function getProps(): array
	{
		return [
			'type' => 'function',
			'function' => [
				'name' => $this->getName(),
				'description' => 'Execute a shell command. Use `allow_shell` with care.',
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
						'timeout' => [
							'type' => 'integer',
							'description' => 'Timeout in seconds (default: 10).',
						],
						'max_output' => [
							'type' => 'integer',
							'description' => 'Max number of bytes to capture from stdout/stderr (default: 10000).',
						],
						'allow_shell' => [
							'type' => 'boolean',
							'description' => 'If true, permit shell metacharacters and run via shell (use with caution).',
						],
						'env' => [
							'type' => 'object',
							'description' => 'Environment variables to pass to the process.',
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

