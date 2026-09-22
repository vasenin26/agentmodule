<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyException;
use Anymodule\Agentmodule\Application\Tools\Terminal\Kill;
use PHPUnit\Framework\TestCase;

class KillTest extends TestCase
{
    public function testReturnsNullWithoutJobId(): void
    {
        $tool = new Kill(new FakeCommandProxyClient());
        $this->assertNull($tool->execute([]));
    }

    public function testDefaultsSignalToTerm(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-1', 'status' => 'killed', 'exit_code' => null]);
        $tool = new Kill($client);

        $result = $tool->execute(['job_id' => 'job-1']);

        $this->assertTrue($result->status);
        $this->assertSame('killed', $result->payload['status']);
        $this->assertSame('TERM', $client->calls[0]['request']['signal']);
    }

    public function testAcceptsKillSignal(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-1', 'status' => 'killed', 'exit_code' => null]);
        $tool = new Kill($client);

        $tool->execute(['job_id' => 'job-1', 'signal' => 'kill']);

        $this->assertSame('KILL', $client->calls[0]['request']['signal']);
    }

    public function testRejectsInvalidSignal(): void
    {
        $tool = new Kill(new FakeCommandProxyClient());

        $result = $tool->execute(['job_id' => 'job-1', 'signal' => 'HUP']);

        $this->assertFalse($result->status);
    }

    public function testReturnsAlreadyExitedStatus(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-1', 'status' => 'already_exited', 'exit_code' => 0]);
        $tool = new Kill($client);

        $result = $tool->execute(['job_id' => 'job-1']);

        $this->assertTrue($result->status);
        $this->assertSame('already_exited', $result->payload['status']);
        $this->assertSame(0, $result->payload['exit_code']);
    }

    public function testReturnsFailureOnProxyError(): void
    {
        $client = new FakeCommandProxyClient(['error' => 'job_not_found', 'message' => 'No such job']);
        $tool = new Kill($client);

        $result = $tool->execute(['job_id' => 'missing']);

        $this->assertFalse($result->status);
        $this->assertSame('No such job', $result->message);
    }

    public function testReturnsFailureOnTransportException(): void
    {
        $client = new FakeCommandProxyClient(null, new CommandProxyException('boom'));
        $tool = new Kill($client);

        $result = $tool->execute(['job_id' => 'job-1']);

        $this->assertFalse($result->status);
        $this->assertSame('boom', $result->message);
    }

    public function testGetName(): void
    {
        $this->assertEquals('terminal-kill', (new Kill())->getName());
    }
}
