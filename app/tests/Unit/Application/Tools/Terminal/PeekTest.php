<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyException;
use Anymodule\Agentmodule\Application\Tools\Terminal\Peek;
use PHPUnit\Framework\TestCase;

class PeekTest extends TestCase
{
    public function testReturnsNullWithoutJobId(): void
    {
        $tool = new Peek(new FakeCommandProxyClient());
        $this->assertNull($tool->execute([]));
    }

    public function testSendsPeekActionNonBlocking(): void
    {
        $client = new FakeCommandProxyClient([
            'job_id' => 'job-1',
            'status' => 'running',
            'exit_code' => null,
            'stdout_delta' => '',
            'stdout_offset' => 0,
            'stderr_delta' => '',
            'stderr_offset' => 0,
        ]);
        $tool = new Peek($client);

        $result = $tool->execute(['job_id' => 'job-1']);

        $this->assertTrue($result->status);
        $this->assertStringContainsString('running', $result->message);
        $this->assertSame('', $result->payload['stdout_delta']);

        $request = $client->calls[0]['request'];
        $this->assertSame('peek', $request['action']);
        $this->assertArrayNotHasKey('timeout', $request);
        // peek must never block for long
        $this->assertLessThanOrEqual(5, $client->calls[0]['timeout']);
    }

    public function testReturnsFailureOnProxyError(): void
    {
        $client = new FakeCommandProxyClient(['error' => 'job_not_found', 'message' => 'No such job']);
        $tool = new Peek($client);

        $result = $tool->execute(['job_id' => 'missing']);

        $this->assertFalse($result->status);
        $this->assertSame('No such job', $result->message);
    }

    public function testReturnsFailureOnTransportException(): void
    {
        $client = new FakeCommandProxyClient(null, new CommandProxyException('boom'));
        $tool = new Peek($client);

        $result = $tool->execute(['job_id' => 'job-1']);

        $this->assertFalse($result->status);
        $this->assertSame('boom', $result->message);
    }

    public function testGetName(): void
    {
        $this->assertEquals('terminal-peek', (new Peek())->getName());
    }
}
