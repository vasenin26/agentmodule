<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyException;
use Anymodule\Agentmodule\Application\Tools\Terminal\Wait;
use PHPUnit\Framework\TestCase;

class WaitTest extends TestCase
{
    public function testReturnsNullWithoutJobId(): void
    {
        $tool = new Wait(new FakeCommandProxyClient());
        $this->assertNull($tool->execute([]));
    }

    public function testSendsWaitActionWithDefaults(): void
    {
        $client = new FakeCommandProxyClient([
            'job_id' => 'job-1',
            'status' => 'running',
            'exit_code' => null,
            'stdout_delta' => 'hello',
            'stdout_offset' => 5,
            'stderr_delta' => '',
            'stderr_offset' => 0,
        ]);
        $tool = new Wait($client);

        $result = $tool->execute(['job_id' => 'job-1']);

        $this->assertTrue($result->status);
        $this->assertSame('running', $result->payload['status']);
        $this->assertSame(5, $result->payload['stdout_offset']);
        $this->assertSame('hello', $result->payload['stdout_delta']);
        $this->assertStringContainsString('running', $result->message);

        $request = $client->calls[0]['request'];
        $this->assertSame('wait', $request['action']);
        $this->assertSame('job-1', $request['job_id']);
        $this->assertSame(15, $request['timeout']);
        $this->assertSame(0, $request['stdout_offset']);
        $this->assertSame(0, $request['stderr_offset']);
    }

    public function testClampsTimeoutToHardCap(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-1', 'status' => 'running']);
        $tool = new Wait($client);

        $tool->execute(['job_id' => 'job-1', 'timeout' => 9999]);

        $this->assertSame(30, $client->calls[0]['request']['timeout']);
        // socket read timeout must be at least the clamped proxy-side wait timeout
        $this->assertGreaterThanOrEqual(30, $client->calls[0]['timeout']);
    }

    public function testClampsNonPositiveTimeoutToAtLeastOne(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-1', 'status' => 'running']);
        $tool = new Wait($client);

        $tool->execute(['job_id' => 'job-1', 'timeout' => 0]);

        $this->assertSame(1, $client->calls[0]['request']['timeout']);
    }

    public function testPassesThroughOffsets(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-1', 'status' => 'exited', 'exit_code' => 0]);
        $tool = new Wait($client);

        $tool->execute(['job_id' => 'job-1', 'stdout_offset' => 100, 'stderr_offset' => 20]);

        $this->assertSame(100, $client->calls[0]['request']['stdout_offset']);
        $this->assertSame(20, $client->calls[0]['request']['stderr_offset']);
    }

    public function testReturnsFailureOnProxyError(): void
    {
        $client = new FakeCommandProxyClient(['error' => 'job_not_found', 'message' => 'No such job']);
        $tool = new Wait($client);

        $result = $tool->execute(['job_id' => 'missing']);

        $this->assertFalse($result->status);
        $this->assertSame('No such job', $result->message);
    }

    public function testReturnsFailureOnTransportException(): void
    {
        $client = new FakeCommandProxyClient(null, new CommandProxyException('boom'));
        $tool = new Wait($client);

        $result = $tool->execute(['job_id' => 'job-1']);

        $this->assertFalse($result->status);
        $this->assertSame('boom', $result->message);
    }

    public function testGetName(): void
    {
        $this->assertEquals('terminal-wait', (new Wait())->getName());
    }
}
