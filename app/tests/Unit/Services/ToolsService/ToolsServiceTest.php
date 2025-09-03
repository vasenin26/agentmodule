<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ToolsService;

use Anymodule\Agentmodule\Interface\ToolInterface;
use Anymodule\Agentmodule\Services\ToolsService\ToolsService;
use Mockery;
use PHPUnit\Framework\TestCase;

class ToolsServiceTest extends TestCase
{
    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }

    public function testCallTool()
    {
        $tool = Mockery::mock(ToolInterface::class);
        $tool->shouldReceive('getProps')
            ->with('result')
            ->andReturn(['name' => 'result', 'description' => 'Test tool']);
        $tool->shouldReceive('execute')
            ->with([])
            ->andReturn('test result');

        $service = new ToolsService($tool, []);

        $result = $service->callTool('result', '{}');

        $this->assertEquals('test result', $result);
    }

    public function testIsResultFunction()
    {
        $service = new ToolsService(Mockery::mock(ToolInterface::class), []);
        $this->assertTrue($service->isResultFunction('result'));
    }
}
