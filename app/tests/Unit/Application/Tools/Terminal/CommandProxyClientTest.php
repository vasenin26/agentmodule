<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyClient;
use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyException;
use PHPUnit\Framework\TestCase;

class CommandProxyClientTest extends TestCase
{
    private string $socketPath;

    /** @var resource|null */
    private $serverProcess = null;

    private string|false $originalEnv;

    protected function setUp(): void
    {
        $this->socketPath = sys_get_temp_dir() . '/command_proxy_test_' . uniqid() . '.sock';
        $this->originalEnv = getenv('COMMAND_PROXY_SOCKET');
    }

    protected function tearDown(): void
    {
        if (is_resource($this->serverProcess)) {
            proc_terminate($this->serverProcess);
            proc_close($this->serverProcess);
        }
        if (file_exists($this->socketPath)) {
            @unlink($this->socketPath);
        }
        if ($this->originalEnv === false) {
            putenv('COMMAND_PROXY_SOCKET');
        } else {
            putenv('COMMAND_PROXY_SOCKET=' . $this->originalEnv);
        }
    }

    private function startFakeServer(string $rawResponseLine): void
    {
        $encodedResponse = var_export($rawResponseLine . "\n", true);
        $encodedSocketPath = var_export($this->socketPath, true);

        $script = <<<PHP
\$server = stream_socket_server('unix://' . {$encodedSocketPath}, \$errno, \$errstr);
if (!\$server) { exit(1); }
\$conn = @stream_socket_accept(\$server, 5);
if (!\$conn) { exit(1); }
@fread(\$conn, 65536);
@fwrite(\$conn, {$encodedResponse});
fclose(\$conn);
fclose(\$server);
PHP;

        $this->serverProcess = proc_open(['php', '-r', $script], [], $pipes);

        $deadline = microtime(true) + 2;
        while (!file_exists($this->socketPath) && microtime(true) < $deadline) {
            usleep(10000);
        }
    }

    public function testSendReturnsDecodedResponseOnSuccess(): void
    {
        $this->startFakeServer('{"stdout":"ok","exit_code":0}');

        $client = new CommandProxyClient($this->socketPath);
        $response = $client->send(['action' => 'exec', 'command' => 'echo ok'], 5);

        $this->assertSame(['stdout' => 'ok', 'exit_code' => 0], $response);
    }

    public function testSendReturnsProxyErrorResponseAsIs(): void
    {
        $this->startFakeServer('{"error":"job_not_found","message":"No such job"}');

        $client = new CommandProxyClient($this->socketPath);
        $response = $client->send(['action' => 'peek', 'job_id' => 'missing'], 5);

        $this->assertSame(['error' => 'job_not_found', 'message' => 'No such job'], $response);
    }

    public function testThrowsOnInvalidJsonResponse(): void
    {
        $this->startFakeServer('not json at all');

        $client = new CommandProxyClient($this->socketPath);

        $this->expectException(CommandProxyException::class);
        $client->send(['action' => 'exec', 'command' => 'echo ok'], 5);
    }

    public function testThrowsWhenSocketFileDoesNotExist(): void
    {
        $client = new CommandProxyClient(sys_get_temp_dir() . '/nonexistent_' . uniqid() . '.sock');

        $this->expectException(CommandProxyException::class);
        $client->send(['action' => 'exec', 'command' => 'echo ok'], 5);
    }

    public function testThrowsWhenSocketPathNotConfigured(): void
    {
        putenv('COMMAND_PROXY_SOCKET');
        $client = new CommandProxyClient(null);

        $this->expectException(CommandProxyException::class);
        $client->send(['action' => 'exec', 'command' => 'echo ok'], 5);
    }

    public function testUsesSocketPathFromEnvWhenNotInjected(): void
    {
        $this->startFakeServer('{"stdout":"from-env","exit_code":0}');
        putenv('COMMAND_PROXY_SOCKET=' . $this->socketPath);

        $client = new CommandProxyClient();
        $response = $client->send(['action' => 'exec', 'command' => 'echo ok'], 5);

        $this->assertSame(['stdout' => 'from-env', 'exit_code' => 0], $response);
    }
}
