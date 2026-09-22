<?php

namespace Anymodule\Agentmodule\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Logger\Log;

/**
 * Transport for the Command Proxy Unix-socket protocol (docs/command-proxy-protocol.md).
 * One send() = one connection: connect, write one JSON request, read one JSON response line, close.
 */
class CommandProxyClient implements CommandProxyClientInterface
{
    private const CONNECT_TIMEOUT = 2;
    private const MAX_RESPONSE_LINE = 1048576; // 1MB

    public function __construct(
        private readonly ?string $socketPath = null,
    ) {
    }

    /**
     * @throws CommandProxyException on any transport-level failure (missing/unreachable socket,
     *         write/read failure, timeout, malformed JSON). A well-formed proxy error response
     *         (`{"error": ...}`) is returned normally — interpreting it is the caller's job.
     */
    public function send(array $request, int $timeoutSeconds): array
    {
        $socketPath = $this->socketPath ?? getenv('COMMAND_PROXY_SOCKET');

        if (!$socketPath) {
            Log::error('COMMAND_PROXY_SOCKET environment variable is not set', ['request' => $request]);
            throw new CommandProxyException('COMMAND_PROXY_SOCKET environment variable is not set');
        }

        $requestJson = json_encode($request);
        if ($requestJson === false) {
            throw new CommandProxyException('Failed to encode request JSON');
        }

        $uri = 'unix://' . $socketPath;
        $errNo = 0;
        $errStr = '';
        $fp = @stream_socket_client($uri, $errNo, $errStr, self::CONNECT_TIMEOUT);

        if (!$fp) {
            Log::error('Failed to connect to Command Proxy socket', [
                'socket_path' => $socketPath,
                'error_code' => $errNo,
                'error_message' => $errStr,
            ]);
            throw new CommandProxyException("Failed to connect to socket: $errStr ($errNo)");
        }

        try {
            stream_set_blocking($fp, true);

            $bytesWritten = @fwrite($fp, $requestJson);
            if ($bytesWritten === false) {
                throw new CommandProxyException('Failed to write request to socket');
            }

            stream_set_timeout($fp, $timeoutSeconds);
            $response = @fgets($fp, self::MAX_RESPONSE_LINE);

            $info = stream_get_meta_data($fp);
            if (isset($info['timed_out']) && $info['timed_out']) {
                throw new CommandProxyException("Read timed out after {$timeoutSeconds}s");
            }

            if ($response === false || strlen($response) === 0) {
                throw new CommandProxyException('Empty response from proxy');
            }

            $response = trim($response);
            $decoded = json_decode($response, true);

            if ($decoded === null) {
                throw new CommandProxyException(
                    'Invalid JSON response from proxy: ' . substr($response, 0, 200)
                );
            }

            return $decoded;
        } finally {
            if (is_resource($fp)) {
                fclose($fp);
            }
        }
    }
}
