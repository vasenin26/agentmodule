<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Entity\ToolResult;
use Anymodule\Agentmodule\Interface\Tools\ToolInterface;
use Anymodule\Agentmodule\Utils\Log;

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
			$timeout = isset($args['timeout']) ? (int)$args['timeout'] : 120;
			$maxOutput = isset($args['max_output']) ? (int)$args['max_output'] : 10000;
			$allowShell = isset($args['allow_shell']) ? (bool)$args['allow_shell'] : false;

			if (!$allowShell) {
				// basic safety: prevent common shell metacharacters when shell not explicitly allowed
				if (preg_match('/[;&|`$<>\n\r]/', $command)) {
					return new ToolResult(false, 'Shell metacharacters detected; set allow_shell=true to permit.');
				}
			}

			// Check if COMMAND_PROXY_SOCKET is available
			$socketPath = getenv('COMMAND_PROXY_SOCKET');
			if (!$socketPath) {
				Log::error('COMMAND_PROXY_SOCKET environment variable is not set', [
					'command' => $command,
					'cwd' => $cwd,
				]);
				return new ToolResult(false, 'COMMAND_PROXY_SOCKET environment variable is not set');
			}

			// Validate socket path and check accessibility
			$socketCheck = $this->checkSocketAccessibility($socketPath);
			if (!$socketCheck['accessible']) {
				Log::error('Socket is not accessible', [
					'socket_path' => $socketPath,
					'reason' => $socketCheck['reason'],
					'details' => $socketCheck['details'],
					'command' => $command,
				]);
				return new ToolResult(false, $socketCheck['message']);
			}

			Log::info('Executing command via socket', [
				'command' => $command,
				'socket_path' => $socketPath,
				'cwd' => $cwd,
				'timeout' => $timeout,
			]);

			return $this->executeViaSocket($socketPath, $command, $cwd, $timeout, $maxOutput, $args['env'] ?? null);
		} catch (\Throwable $e) {
			Log::exception($e, 'Terminal Run execution error', [
				'command' => $args['command'] ?? null,
			]);
			return new ToolResult(false, $e->getMessage(), ['exception' => get_class($e)]);
		}
	}

	/**
	 * Check socket accessibility and provide detailed diagnostic information
	 * 
	 * @param string $socketPath Path to the Unix socket
	 * @return array Array with keys: accessible (bool), reason (string), details (array), message (string)
	 */
	private function checkSocketAccessibility(string $socketPath): array
	{
		$details = [
			'socket_path' => $socketPath,
			'exists' => false,
			'readable' => false,
			'writable' => false,
			'is_socket' => false,
			'permissions' => null,
			'owner' => null,
			'group' => null,
			'directory_exists' => false,
			'directory_readable' => false,
			'directory_writable' => false,
			'current_user' => get_current_user(),
			'current_uid' => getmyuid(),
			'current_gid' => getmygid(),
		];

		// Check if socket file exists
		if (!file_exists($socketPath)) {
			$details['exists'] = false;
			
			// Check if directory exists
			$dirPath = dirname($socketPath);
			$details['directory_exists'] = is_dir($dirPath);
			$details['directory_readable'] = is_readable($dirPath);
			$details['directory_writable'] = is_writable($dirPath);
			
			if (!$details['directory_exists']) {
				return [
					'accessible' => false,
					'reason' => 'socket_directory_not_found',
					'details' => $details,
					'message' => "Socket directory does not exist: " . dirname($socketPath),
				];
			}
			
			return [
				'accessible' => false,
				'reason' => 'socket_not_found',
				'details' => $details,
				'message' => "Socket file does not exist: $socketPath. The service may not be running or the socket path is incorrect.",
			];
		}

		$details['exists'] = true;
		$details['is_socket'] = filetype($socketPath) === 'socket';
		
		// Get file permissions
		$perms = @fileperms($socketPath);
		if ($perms !== false) {
			$details['permissions'] = substr(sprintf('%o', $perms), -4);
		}
		
		// Get file owner info
		$ownerInfo = @posix_getpwuid(@fileowner($socketPath));
		$details['owner'] = $ownerInfo !== false ? $ownerInfo['name'] : 'unknown';
		
		$groupInfo = @posix_getgrgid(@filegroup($socketPath));
		$details['group'] = $groupInfo !== false ? $groupInfo['name'] : 'unknown';
		
		// Check readability
		$details['readable'] = is_readable($socketPath);
		$details['writable'] = is_writable($socketPath);
		
		// Check directory permissions
		$dirPath = dirname($socketPath);
		$details['directory_exists'] = is_dir($dirPath);
		$details['directory_readable'] = is_readable($dirPath);
		$details['directory_writable'] = is_writable($dirPath);
		
		// Check if it's actually a socket
		if (!$details['is_socket']) {
			$fileType = filetype($socketPath);
			return [
				'accessible' => false,
				'reason' => 'not_a_socket',
				'details' => $details,
				'message' => "Path exists but is not a socket file. File type: $fileType",
			];
		}
		
		// Check permissions
		if (!$details['readable']) {
			return [
				'accessible' => false,
				'reason' => 'socket_not_readable',
				'details' => $details,
				'message' => "Socket file exists but is not readable. Owner: {$details['owner']}, Permissions: {$details['permissions']}",
			];
		}
		
		if (!$details['writable']) {
			return [
				'accessible' => false,
				'reason' => 'socket_not_writable',
				'details' => $details,
				'message' => "Socket file exists but is not writable. Owner: {$details['owner']}, Permissions: {$details['permissions']}",
			];
		}
		
		// Socket exists and has proper permissions, but connection might still fail if service is not listening
		return [
			'accessible' => true,
			'reason' => 'socket_accessible',
			'details' => $details,
			'message' => 'Socket file exists and has proper permissions',
		];
	}

	/**
	 * Execute command via Unix socket (Command Proxy)
	 */
	private function executeViaSocket(string $socketPath, string $command, ?string $cwd, int $timeout, int $maxOutput, ?array $env): ToolResult
	{
		$start = microtime(true);

		// Build command with cwd and env if needed
		$fullCommand = $command;
		if ($cwd !== null) {
			$fullCommand = "cd " . escapeshellarg($cwd) . " && " . $command;
		}
		if (is_array($env) && !empty($env)) {
			$envExports = [];
			foreach ($env as $key => $value) {
				$envExports[] = "export " . escapeshellarg($key) . "=" . escapeshellarg((string)$value);
			}
			$fullCommand = implode(" && ", $envExports) . " && " . $fullCommand;
		}

		// Prepare JSON request
		$request = json_encode([
			'action' => 'exec',
			'command' => $fullCommand,
		]);

		if ($request === false) {
			return new ToolResult(false, 'Failed to encode request JSON');
		}

		// Connect to Unix socket
		$uri = "unix://" . $socketPath;
		Log::info('Attempting to connect to socket', [
			'uri' => $uri,
			'socket_path' => $socketPath,
			'timeout' => 2,
		]);
		
		$errNo = 0;
		$errStr = '';
		$fp = @stream_socket_client($uri, $errNo, $errStr, 2);
		if (!$fp) {
			// Detailed error logging for connection failures
			$errorDetails = [
				'error_code' => $errNo,
				'error_message' => $errStr,
				'socket_path' => $socketPath,
				'uri' => $uri,
			];
			
			// Re-check socket state for additional diagnostics
			if (file_exists($socketPath)) {
				$errorDetails['socket_exists'] = true;
				$errorDetails['socket_type'] = filetype($socketPath);
				$errorDetails['socket_readable'] = is_readable($socketPath);
				$errorDetails['socket_writable'] = is_writable($socketPath);
				
				// Common error code mappings
				$errorMessages = [
					2 => 'ENOENT - Socket file not found (may have been deleted)',
					13 => 'EACCES - Permission denied (check socket permissions and ownership)',
					111 => 'ECONNREFUSED - Connection refused (service not listening on socket)',
					110 => 'ETIMEDOUT - Connection timeout',
				];
				
				if (isset($errorMessages[$errNo])) {
					$errorDetails['diagnosis'] = $errorMessages[$errNo];
				}
				
				// Additional checks for ECONNREFUSED
				if ($errNo === 111) {
					$errorDetails['likely_cause'] = 'The socket file exists, but the service is not accepting connections. Possible reasons: service not running, service crashed, service not ready to accept connections, or socket is stale (old socket file left behind).';
				}
			} else {
				$errorDetails['socket_exists'] = false;
				$errorDetails['likely_cause'] = 'Socket file does not exist. The service may not be running or the socket path is incorrect.';
			}
			
			Log::error('Failed to connect to socket', $errorDetails);
			
			$userMessage = "Failed to connect to socket: $errStr ($errNo)";
			if (isset($errorDetails['diagnosis'])) {
				$userMessage .= ". " . $errorDetails['diagnosis'];
			}
			
			return new ToolResult(false, $userMessage);
		}
		
		Log::info('Successfully connected to socket', [
			'socket_path' => $socketPath,
		]);

		try {
			// Send request
			stream_set_blocking($fp, true);
			$bytesWritten = @fwrite($fp, $request);
			if ($bytesWritten === false) {
				$lastError = error_get_last();
				Log::error('Failed to write request to socket', [
					'socket_path' => $socketPath,
					'request_size' => strlen($request),
					'last_error' => $lastError,
				]);
				fclose($fp);
				return new ToolResult(false, 'Failed to write request to socket');
			}
			
			Log::info('Request sent to socket', [
				'socket_path' => $socketPath,
				'bytes_written' => $bytesWritten,
			]);

			// Set read timeout
			stream_set_timeout($fp, $timeout);

			// Read JSON response line (server sends JSON followed by \n)
			// Using fgets() instead of reading until EOF, because server keeps connection open
			$response = @fgets($fp, 1048576); // 1MB max line

			// Check for timeout
			$info = stream_get_meta_data($fp);
			if (isset($info['timed_out']) && $info['timed_out']) {
				Log::error('Read timeout from socket', [
					'socket_path' => $socketPath,
					'timeout' => $timeout,
				]);
				fclose($fp);
				return new ToolResult(false, "Read timed out after {$timeout}s");
			}

			// Close connection immediately after receiving response
			fclose($fp);
			$fp = null; // Mark as closed

			// Handle empty or failed response
			if ($response === false || strlen($response) === 0) {
				Log::error('Empty response received from socket', [
					'socket_path' => $socketPath,
					'command' => $command,
					'likely_cause' => 'The service closed the connection without sending data or read failed.',
				]);
				return new ToolResult(false, 'Empty response from proxy. The service may have closed the connection without sending data.');
			}

			$response = trim($response);

			$responseSize = strlen($response);
			Log::info('Response received from socket', [
				'socket_path' => $socketPath,
				'response_size' => $responseSize,
			]);

			// Parse JSON response
			$obj = json_decode($response, true);
			if ($obj === null) {
				$jsonError = json_last_error_msg();
				Log::error('Failed to parse JSON response from socket', [
					'socket_path' => $socketPath,
					'json_error' => $jsonError,
					'json_error_code' => json_last_error(),
					'response_preview' => substr($response, 0, 500),
					'response_size' => $responseSize,
					'response_hex' => bin2hex(substr($response, 0, 100)), // Show first bytes in hex for debugging
				]);
				
				$errorMessage = 'Invalid JSON response from proxy';
				if ($responseSize > 0) {
					$errorMessage .= ': ' . substr($response, 0, 200);
				} else {
					$errorMessage .= ' (empty response)';
				}
				
				return new ToolResult(false, $errorMessage);
			}

			// Check for error response
			if (isset($obj['error'])) {
				Log::error('Proxy returned error', [
					'socket_path' => $socketPath,
					'proxy_error' => $obj['error'],
					'command' => $command,
				]);
				return new ToolResult(false, 'Proxy error: ' . $obj['error']);
			}

			// Extract results
			$stdout = isset($obj['stdout']) ? (string)$obj['stdout'] : '';
			$stderr = isset($obj['stderr']) ? (string)$obj['stderr'] : '';
			$exitCode = isset($obj['exit_code']) ? (int)$obj['exit_code'] : -1;

			// Apply max output limit
			if (strlen($stdout) > $maxOutput) {
				$stdout = substr($stdout, 0, $maxOutput) . "\n...[truncated]";
			}
			if (strlen($stderr) > $maxOutput) {
				$stderr = substr($stderr, 0, $maxOutput) . "\n...[truncated]";
			}

			$elapsed = microtime(true) - $start;

			$payload = [
				'command' => $command,
				'cwd' => $cwd ?? getcwd(),
				'exit_code' => $exitCode,
				'stdout' => $stdout,
				'stderr' => $stderr,
				'elapsed' => round($elapsed, 3),
			];

			$message = 'Command executed';
			if ($stdout !== '') {
				$message .= "\n\nSTDOUT:\n```\n" . $stdout . "\n```";
			} else {
				$message .= "\n\nSTDOUT: (empty)";
			}
			if ($stderr !== '') {
				$message .= "\n\nSTDERR:\n```\n" . $stderr . "\n```";
			}

			Log::info('Command executed successfully via socket', [
				'socket_path' => $socketPath,
				'command' => $command,
				'exit_code' => $exitCode,
				'elapsed' => round($elapsed, 3),
			]);
			
			return new ToolResult(true, $message, $payload);
		} catch (\Throwable $e) {
			if (is_resource($fp)) {
				fclose($fp);
			}
			Log::exception($e, 'Error during socket communication', [
				'socket_path' => $socketPath,
				'command' => $command,
				'cwd' => $cwd,
			]);
			throw $e;
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

