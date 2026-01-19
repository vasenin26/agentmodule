<?php

namespace Anymodule\Agentmodule\Tests\Unit\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Context\CodeContext;
use Anymodule\Agentmodule\Application\Workflow\Nodes\Tester;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Factory\ToolServiceFactory;
use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Anymodule\Agentmodule\Interface\ContextActionContract;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\ProcessHandlerInterface;
use Anymodule\Agentmodule\Interface\SubtaskCreatorInterface;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ServiceMessage;
use Vasenin26\Conversation\Messages\ToolMessage;

final class TesterTest extends TestCase
{
    public function testTesterMarksSuccessAndWritesReport(): void
    {
        $task = new Task(
            id: 1,
            type: 'code',
            conversationId: 1,
            messages: [],
            context: ['tasks' => [], 'payload' => []],
            projectId: 1,
            resultRequired: false,
        );

        $chat = new Chat();
        $ctx = new CodeContext($task, new ContextConversation(new Context(tasks: [], payload: []), $chat));

        $subtaskCalls = [];
        $subtaskCreator = $this->createMock(SubtaskCreatorInterface::class);
        $subtaskCreator->expects($this->exactly(2))
            ->method('createSubtask')
            ->willReturnCallback(function (string $type) use (&$subtaskCalls) {
                $subtaskCalls[] = $type;
                return $this->createMock(ProcessHandlerInterface::class);
            });

        $runnerFactory = new class($subtaskCreator) implements ActionRunnerFactoryInterface {
            public function __construct(private SubtaskCreatorInterface $subtaskCreator)
            {
            }

            public function createForTask(Task $task, array $actions): \Anymodule\Agentmodule\Interface\ActionRunnerInterface
            {
                return new \Anymodule\Agentmodule\Application\ActionRunner($actions, $this->subtaskCreator);
            }
        };

        $chatAgentFactory = $this->createMock(ChatAgentFactoryInterface::class);
        $chatAgentFactory->method('createModelContextAgent')->willReturn(new class(true) implements ContextActionContract {
            public function __construct(private bool $success)
            {
            }

            public function execute(\Anymodule\Agentmodule\Entity\ContextConversation $conversation): \Generator
            {
                $payload = [
                    'success' => $this->success,
                    'summary' => 'All tests passed',
                    'errors' => '',
                ];

                $conversation->conversation->addMessage(new ToolMessage(
                    true,
                    'tool-1',
                    'result',
                    '{}',
                    json_encode(['message' => 'Result captured', 'payload' => $payload])
                ));

                yield new ProcessingResult(
                    completed: true,
                    answer: 'done',
                    conversation: $conversation->conversation,
                    context: null,
                    modelName: 'test-model',
                    contextFill: 0,
                    promptTokens: 1,
                    completionTokens: 1,
                    totalTokens: 2,
                );
            }
        });

        $gitRepoProvider = $this->createMock(GitRepoProviderInterface::class);
        $pageContextFactory = $this->createMock(PageContextServiceFactoryInterface::class);
        $toolServiceFactory = new ToolServiceFactory($gitRepoProvider, $pageContextFactory);

        $agentMetaProvider = $this->createMock(AgentMetaProviderInterface::class);
        $agentMetaProvider->method('getDefaultModel')->willReturn('test-model');

        $node = new Tester(
            $runnerFactory,
            $chatAgentFactory,
            $toolServiceFactory,
            $agentMetaProvider,
            $gitRepoProvider,
        );

        foreach ($node->process($ctx) as $step) {
            // exhaust
        }

        $this->assertSame(['build-test-chat', 'run-tests'], $subtaskCalls);
        $this->assertTrue($ctx->testFinished());
        $this->assertTrue($ctx->testSucceed());

        $assistant = $this->findLastMessageOfType($chat, AssistantMessage::class);
        $this->assertNotNull($assistant);
        $this->assertStringContainsString('Test result: PASSED', $assistant->content);

        $this->assertTrue($this->hasServiceKey($chat, 'build-test-chat'));
        $this->assertTrue($this->hasServiceKey($chat, 'run-tests'));
    }

    public function testTesterMarksFailureAndWritesErrors(): void
    {
        $task = new Task(
            id: 2,
            type: 'code',
            conversationId: 2,
            messages: [],
            context: ['tasks' => [], 'payload' => []],
            projectId: 1,
            resultRequired: false,
        );

        $chat = new Chat();
        $ctx = new CodeContext($task, new ContextConversation(new Context(tasks: [], payload: []), $chat));

        $subtaskCalls = [];
        $subtaskCreator = $this->createMock(SubtaskCreatorInterface::class);
        $subtaskCreator->expects($this->exactly(2))
            ->method('createSubtask')
            ->willReturnCallback(function (string $type) use (&$subtaskCalls) {
                $subtaskCalls[] = $type;
                return $this->createMock(ProcessHandlerInterface::class);
            });

        $runnerFactory = new class($subtaskCreator) implements ActionRunnerFactoryInterface {
            public function __construct(private SubtaskCreatorInterface $subtaskCreator)
            {
            }

            public function createForTask(Task $task, array $actions): \Anymodule\Agentmodule\Interface\ActionRunnerInterface
            {
                return new \Anymodule\Agentmodule\Application\ActionRunner($actions, $this->subtaskCreator);
            }
        };

        $chatAgentFactory = $this->createMock(ChatAgentFactoryInterface::class);
        $chatAgentFactory->method('createModelContextAgent')->willReturn(new class(false) implements ContextActionContract {
            public function __construct(private bool $success)
            {
            }

            public function execute(\Anymodule\Agentmodule\Entity\ContextConversation $conversation): \Generator
            {
                $payload = [
                    'success' => $this->success,
                    'summary' => 'Some tests failed',
                    'errors' => "PHPUnit failed\nAssertionError",
                ];

                $conversation->conversation->addMessage(new ToolMessage(
                    true,
                    'tool-1',
                    'result',
                    '{}',
                    json_encode(['message' => 'Result captured', 'payload' => $payload])
                ));

                yield new ProcessingResult(
                    completed: true,
                    answer: 'done',
                    conversation: $conversation->conversation,
                    context: null,
                    modelName: 'test-model',
                    contextFill: 0,
                    promptTokens: 1,
                    completionTokens: 1,
                    totalTokens: 2,
                );
            }
        });

        $gitRepoProvider = $this->createMock(GitRepoProviderInterface::class);
        $pageContextFactory = $this->createMock(PageContextServiceFactoryInterface::class);
        $toolServiceFactory = new ToolServiceFactory($gitRepoProvider, $pageContextFactory);

        $agentMetaProvider = $this->createMock(AgentMetaProviderInterface::class);
        $agentMetaProvider->method('getDefaultModel')->willReturn('test-model');

        $node = new Tester(
            $runnerFactory,
            $chatAgentFactory,
            $toolServiceFactory,
            $agentMetaProvider,
            $gitRepoProvider,
        );

        foreach ($node->process($ctx) as $step) {
            // exhaust
        }

        $this->assertSame(['build-test-chat', 'run-tests'], $subtaskCalls);
        $this->assertTrue($ctx->testFinished());
        $this->assertFalse($ctx->testSucceed());

        $assistant = $this->findLastMessageOfType($chat, AssistantMessage::class);
        $this->assertNotNull($assistant);
        $this->assertStringContainsString('Test result: FAILED', $assistant->content);
        $this->assertStringContainsString('Errors:', $assistant->content);
        $this->assertStringContainsString('PHPUnit failed', $assistant->content);
    }

    /**
     * @template T of Message
     * @param class-string<T> $class
     * @return T|null
     */
    private function findLastMessageOfType(Chat $chat, string $class): ?Message
    {
        $found = null;
        foreach ($chat->getMessages() as $msg) {
            if ($msg instanceof $class) {
                $found = $msg;
            }
        }
        return $found;
    }

    private function hasServiceKey(Chat $chat, string $key): bool
    {
        foreach ($chat->getMessages() as $msg) {
            if ($msg instanceof ServiceMessage && $msg->key === $key) {
                return true;
            }
        }
        return false;
    }
}

