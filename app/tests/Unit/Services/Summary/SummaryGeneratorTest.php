<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services\Summary;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\Summary\SummaryGenerator;
use Anymodule\Agentmodule\Services\ToolsService\ToolsBuilder;
use Anymodule\Agentmodule\Services\ToolsService\ToolsProviderInterfaceService;
use Mockery;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;
use Vasenin26\Conversation\Messages\UserTaskMessage;

class SummaryGeneratorTest extends TestCase
{
    public function test_generate(): void
    {

        $chat = new Chat();

        $systemMessage = new SystemMessage('System PROMPT');
        $userTaskMessage = new UserTaskMessage('Important User Task');
        $userMessage = new UserMessage('Important User Task');
        $assistantMessage = new AssistantMessage('Assistant answer', []);
        $toolResultMessage = new ToolMessage(true, 'id', 'test', '[]', 'ok');

        $chat->addMessage($systemMessage);
        $chat->addMessage($userTaskMessage);
        $chat->addMessage($userMessage);
        $chat->addMessage($assistantMessage);
        $chat->addMessage($toolResultMessage);

        $agent = Mockery::mock(ActionContract::class);
        $agent->shouldReceive('execute')
            ->once()
            ->withArgs(function ($chat) use ($systemMessage, $userTaskMessage, $userMessage, $assistantMessage, $toolResultMessage) {

                $messages = $chat->getMessages();

                $this->assertContains($systemMessage, $messages);
                $this->assertContains($userTaskMessage, $messages);
                $this->assertContains($userMessage, $messages);
                $this->assertContains($assistantMessage, $messages);
                $this->assertContains($toolResultMessage, $messages);

                $lastMessage = $messages[count($messages) - 1];

                $this->assertEquals(SummaryGenerator::PROMPT, $lastMessage->content);

                return true;
            })
            ->andYield(new ProcessingResult(
                completed: true,
                answer: 'OK',
                conversation: new Chat(),
                contextFill: 0,
            ));

        $agentFactory = Mockery::mock(ChatAgentFactoryInterface::class);
        $agentFactory->shouldReceive('createAgent')->andReturn($agent);

        $toolsBuilder = Mockery::mock(ToolsBuilder::class);
        $toolsBuilder->shouldReceive('build')->andReturn(Mockery::mock(ToolsProviderInterfaceService::class));

        $toolService = Mockery::mock(ToolServiceFactoryInterface::class);
        $toolService->shouldReceive('createToolsBuilder')->once()->andReturn($toolsBuilder);

        $generator = new SummaryGenerator($agentFactory, $toolService);

        $result = iterator_to_array($generator->generate($chat));

        Mockery::close();

        $lastResult = $result[count($result) - 1];

        $this->assertEquals('OK', $lastResult->answer);
    }
}