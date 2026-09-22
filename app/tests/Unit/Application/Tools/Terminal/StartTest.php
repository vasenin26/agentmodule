<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Tools\Terminal;

use Anymodule\Agentmodule\Application\Tools\Terminal\CommandProxyException;
use Anymodule\Agentmodule\Application\Tools\Terminal\Start;
use Anymodule\Agentmodule\Entity\ToolResult;
use PHPUnit\Framework\TestCase;

class StartTest extends TestCase
{
    public function testReturnsNullWithoutCommand(): void
    {
        $tool = new Start(new FakeCommandProxyClient());
        $this->assertNull($tool->execute([]));
    }

    public function testReturnsFailureOnEmptyCommand(): void
    {
        $tool = new Start(new FakeCommandProxyClient());
        $result = $tool->execute(['command' => '']);

        $this->assertFalse($result->status);
    }

    public function testSendsStartActionWithDefaults(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-1']);
        $tool = new Start($client);

        $result = $tool->execute(['command' => 'npm run build']);

        $this->assertTrue($result->status);
        $this->assertSame('job-1', $result->payload['job_id']);
        $this->assertCount(1, $client->calls);
        $this->assertSame([
            'action' => 'start',
            'command' => 'npm run build',
            'max_lifetime' => 600,
        ], $client->calls[0]['request']);
    }

    public function testPassesThroughCwdEnvAndMaxLifetime(): void
    {
        $client = new FakeCommandProxyClient(['job_id' => 'job-2']);
        $tool = new Start($client);

        $tool->execute([
            'command' => 'sleep 100',
            'cwd' => '/repo',
            'env' => ['FOO' => 'bar'],
            'max_lifetime' => 60,
        ]);

        $this->assertSame([
            'action' => 'start',
            'command' => 'sleep 100',
            'max_lifetime' => 60,
            'cwd' => '/repo',
            'env' => ['FOO' => 'bar'],
        ], $client->calls[0]['request']);
    }

    public function testReturnsFailureOnProxyError(): void
    {
        $client = new FakeCommandProxyClient(['error' => 'proxy_busy', 'message' => 'Too many jobs']);
        $tool = new Start($client);

        $result = $tool->execute(['command' => 'echo hi']);

        $this->assertFalse($result->status);
        $this->assertSame('Too many jobs', $result->message);
    }

    public function testReturnsFailureOnTransportException(): void
    {
        $client = new FakeCommandProxyClient(null, new CommandProxyException('boom'));
        $tool = new Start($client);

        $result = $tool->execute(['command' => 'echo hi']);

        $this->assertFalse($result->status);
        $this->assertSame('boom', $result->message);
    }

    public function testGetName(): void
    {
        $this->assertEquals('terminal-start', (new Start())->getName());
    }

    public function testGetProps(): void
    {
        $props = (new Start())->getProps();

        $this->assertEquals('terminal-start', $props['function']['name']);
        $this->assertContains('command', $props['function']['parameters']['required']);
    }
}
