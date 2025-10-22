<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\ChatGPTMapper;

use Anymodule\Agentmodule\Application\Mappers\ChatGPTMapper\Mappers\CallToolMapper;
use Anymodule\Agentmodule\Application\ToolsService\ToolsProviderService;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Messages\CallToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;

class CallToolMapperTest extends TestCase
{
    private CallToolMapper $mapper;
    private ToolsProviderService $toolsService;

    protected function setUp(): void
    {
        $this->toolsService = $this->createMock(ToolsProviderService::class);
        $this->mapper = new CallToolMapper($this->toolsService);
    }

    public function testSupportsCallToolMessage(): void
    {
        $message = new CallToolMessage(
            description: "Test description",
            name: "test-tool",
            args: ["param" => "value"]
        );

        $this->assertTrue($this->mapper->supports($message));
    }

    public function testDoesNotSupportOtherMessages(): void
    {
        $message = new UserMessage("test content");

        $this->assertFalse($this->mapper->supports($message));
    }

    public function testMapsCallToolMessageSuccessfully(): void
    {
        $message = new CallToolMessage(
            description: "Test description",
            name: "test-tool",
            args: ["param" => "value"]
        );

        $toolResult = new \Anymodule\Agentmodule\Entity\ToolResult(
            status: true,
            message: "Test result",
            payload: []
        );
        
        $this->toolsService
            ->expects($this->once())
            ->method('callTool')
            ->with('test-tool', '{"param":"value"}')
            ->willReturn($toolResult);

        $result = $this->mapper->map($message);

        $this->assertEquals('user', $result['role']);
        $this->assertStringContainsString('Test description', $result['content']);
        $this->assertStringContainsString('[РЕЗУЛЬТАТ ВЫПОЛНЕНИЯ ФУНКЦИИ: test-tool]', $result['content']);
        $this->assertStringContainsString("Test result", $result['content']);
    }

    public function testMapsCallToolMessageWithError(): void
    {
        $message = new CallToolMessage(
            description: "Test description",
            name: "test-tool",
            args: ["param" => "value"]
        );

        $this->toolsService
            ->expects($this->once())
            ->method('callTool')
            ->with('test-tool', '{"param":"value"}')
            ->willThrowException(new \Exception('Tool execution failed'));

        $result = $this->mapper->map($message);

        $this->assertEquals('user', $result['role']);
        $this->assertStringContainsString('Test description', $result['content']);
        $this->assertStringContainsString('[ОШИБКА ВЫПОЛНЕНИЯ ФУНКЦИИ: test-tool]', $result['content']);
        $this->assertStringContainsString('Ошибка: Tool execution failed', $result['content']);
    }

    public function testThrowsExceptionForUnsupportedMessage(): void
    {
        $message = new UserMessage("test content");

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage("Unsupported message type");

        $this->mapper->map($message);
    }
}
