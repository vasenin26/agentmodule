<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Utils\TokenCounter;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\ServiceMessage;

class ActionRunner
{
    /**
     * @param ActionContract[] $actions
     */
    public function __construct(
        private readonly array $actions
    )
    {
    }

    public function run(Conversation $conversation, TokenCounter $tokenCounter): void
    {
        do {
            $awaitRun = array_diff(array_keys($this->actions), array_map(fn($m) => $m->key, (array)$conversation->getServices()));
            $currentTask = array_pop($awaitRun);

            if (!empty($currentTask)) {
                $taskProcessor = $this->actions[$currentTask] ?? null;

                $message = new ServiceMessage($currentTask, '');
                $link = $conversation->addServiceMessage($message);

                if (is_null($taskProcessor)) {
                    $link->setError('Not found task');
                    continue;
                }

                foreach ($taskProcessor->execute($conversation) as $result) {
                    $link->setMessage($result->answer);

                    if ($result->completed) {
                        $link->complete();

                        $tokenCounter->combine($result);

                        foreach ($result->conversation->getMessages() as $message) {
                            $conversation->addMessage($message);
                        }
                    }
                }
            }
        } while (!empty($awaitRun));
    }
}