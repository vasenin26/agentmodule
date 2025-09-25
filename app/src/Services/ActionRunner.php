<?php

namespace Anymodule\Agentmodule\Services;

use Anymodule\Agentmodule\Utils\TokenCounter;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\ServiceMessage;

class ActionRunner
{
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

                if (is_null($taskProcessor)) {
                    $conversation->addMessage(new ServiceMessage($currentTask, ['error' => 'Not Found task']));
                    continue;
                }

                foreach ($taskProcessor->execute($conversation) as $result) {
                    if ($result->completed) {
                        $conversation->addMessage(new ServiceMessage($currentTask, ['message' => $result->answer]));
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