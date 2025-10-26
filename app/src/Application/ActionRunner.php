<?php

namespace Anymodule\Agentmodule\Application;

use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ActionRunnerInterface;
use Anymodule\Agentmodule\Interface\SubtaskCreatorInterface;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\ServiceMessage;

class ActionRunner implements ActionRunnerInterface
{
    public function __construct(
        private array                            $actions,
        private readonly SubtaskCreatorInterface $subtaskCreator
    )
    {
    }

    /**
     * @param ActionContract[] $actions
     */
    public function run(Conversation $conversation): void
    {
        do {
            $completed = array_map(fn($m) => $m->key, iterator_to_array($conversation->getServices()));
            $awaitRun = array_diff(array_keys($this->actions), $completed);

            $currentTask = array_pop($awaitRun);

            if (!empty($currentTask)) {
                $taskProcessor = $this->actions[$currentTask] ?? null;

                $message = new ServiceMessage($currentTask, '');
                $link = $conversation->addServiceMessage($message);

                if (is_null($taskProcessor)) {
                    $link->setError('Not found task');
                    continue;
                }

                // Создаем подзадачу для каждого ActionContract
                $processHandler = $this->subtaskCreator->createSubtask($currentTask);

                foreach ($taskProcessor->execute($conversation) as $result) {
                    if ($result->answer) {
                        $link->setMessage($result->answer);
                    }

                    if ($result->payload) {
                        $link->setPayload($result->payload);
                    }

                    if ($result->completed) {
                        $link->complete();

                        foreach ($result->conversation->getMessages() as $message) {
                            $conversation->addMessage($message);
                        }
                    }

                    // Обрабатываем результат через ProcessHandler
                    $processHandler->handle($result);
                }
            }
        } while (!empty($awaitRun));
    }
}