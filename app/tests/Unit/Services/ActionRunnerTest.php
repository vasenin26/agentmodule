<?php

namespace Anymodule\Agentmodule\Tests\Unit\Services;

use Anymodule\Agentmodule\Application\ActionRunner;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Utils\TokenCounter;
use PHPUnit\Framework\TestCase;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Interface\MessageLinkInterface;
use Vasenin26\Conversation\Messages\ServiceMessage;

class ActionRunnerTest extends TestCase
{
    public function testRunnerStartsSingleActionWithEmptyChat(): void
    {
        $actionKey = 'testAction';

        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getServices')->willReturn($this->createEmptyGenerator());

        $messageLink = $this->createMock(MessageLinkInterface::class);
        $messageLink->expects($this->once())
            ->method('setMessage')
            ->with('done');
        $messageLink->expects($this->once())
            ->method('complete');

        $conversation->expects($this->once())
            ->method('addServiceMessage')
            ->with($this->callback(function ($msg) use ($actionKey) {
                return $msg instanceof ServiceMessage && $msg->key === $actionKey;
            }))
            ->willReturn($messageLink);

        $resultConversation = $this->createMock(Conversation::class);
        $resultConversation->method('getMessages')->willReturn([]);

        $action = $this->createMock(ActionContract::class);
        $action->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(Conversation::class))
            ->willReturn($this->createResultGenerator(new ProcessingResult(
                completed: true,
                answer: 'done',
                conversation: $resultConversation,
                context: null,
                modelName: null,
                contextFill: 0,
                promptTokens: 1,
                completionTokens: 2,
                totalTokens: 3
            )));

        $runner = new ActionRunner([$actionKey => $action]);
        $counter = new TokenCounter();

        $runner->run($conversation, $counter);

        $this->assertSame([1, 2, 3], $counter->get());
    }

    public function testRunnerSkipsActionIfAlreadyPresentInChat(): void
    {
        $actionKey = 'testAction';

        $conversation = $this->createMock(Conversation::class);
        $conversation->method('getServices')->willReturn($this->createServicesGenerator([
            new ServiceMessage($actionKey, '')
        ]));

        $conversation->expects($this->never())
            ->method('addServiceMessage');

        $action = $this->createMock(ActionContract::class);
        $action->expects($this->never())
            ->method('execute');

        $runner = new ActionRunner([$actionKey => $action]);
        $counter = new TokenCounter();

        $runner->run($conversation, $counter);

        $this->assertSame([0, 0, 0], $counter->get());
    }

    private function createEmptyGenerator(): \Generator
    {
        if (false) {
            yield null;
        }
    }

    /**
     * @param array $items
     */
    private function createServicesGenerator(array $items): \Generator
    {
        foreach ($items as $item) {
            yield $item;
        }
    }

    private function createResultGenerator(ProcessingResult $result): \Generator
    {
        yield $result;
    }

    public function testServiceMessageMarkedCompletedAfterActionFinishes(): void
    {
        $actionKey = 'finalizeTask';

        $conversation = new class implements Conversation {
            private array $services = [];

            public function addMessage(\Vasenin26\Conversation\Message $message): void {}
            public function getMessages(): array { return []; }
            public function getInstructions(): \Generator { if (false) { yield null; } }
            public function serialize(): array { return []; }

            public function getServices(): \Generator
            {
                foreach ($this->services as $row) {
                    yield $row['message'];
                }
            }

            public function addServiceMessage(ServiceMessage $message): MessageLinkInterface
            {
                $index = count($this->services);
                $this->services[$index] = [
                    'message' => $message,
                    'completed' => false,
                ];

                return new class($this->services, $index) implements MessageLinkInterface {
                    public function __construct(private array &$storage, private int $idx) {}
                    public function setMessage(string $message): void { /* not needed */ }
                    public function setError(string $error): void { /* not needed */ }
                    public function setPayload(array $payload): void { /* not needed */ }
                    public function complete(): void { $this->storage[$this->idx]['completed'] = true; }
                };
            }

            public function isServiceCompletedByKey(string $key): bool
            {
                foreach ($this->services as $row) {
                    if ($row['message']->key === $key) {
                        return $row['completed'] === true;
                    }
                }
                return false;
            }
        };

        $resultConversation = $this->createMock(Conversation::class);
        $resultConversation->method('getMessages')->willReturn([]);

        $action = $this->createMock(ActionContract::class);
        $action->expects($this->once())
            ->method('execute')
            ->willReturn($this->createResultGenerator(new ProcessingResult(
                completed: true,
                answer: 'ok',
                conversation: $resultConversation,
                context: null,
                modelName: null,
                contextFill: 0,
                promptTokens: 0,
                completionTokens: 0,
                totalTokens: 0
            )));

        $runner = new ActionRunner([$actionKey => $action]);
        $counter = new TokenCounter();

        $runner->run($conversation, $counter);

        $this->assertTrue($conversation->isServiceCompletedByKey($actionKey));
    }
}