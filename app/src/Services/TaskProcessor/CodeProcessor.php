<?php

namespace Anymodule\Agentmodule\Services\TaskProcessor;

use Anymodule\Agentmodule\Entity\LLMResult;
use Anymodule\Agentmodule\Entity\Task;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\LLMFactoryInterface;
use Anymodule\Agentmodule\Interface\Page\PageContextServiceFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\RepositoryService\RepositoryProvider;
use Vasenin26\Conversation\Interface\ConversationFactoryInterface;

class CodeProcessor implements \Anymodule\Agentmodule\Interface\Task\TaskProcessor
{
    public function __construct(
        private ToolServiceFactoryInterface  $toolsFactory,
        private LLMFactoryInterface          $chatFactory,
        private ConversationFactoryInterface $conversationFactory,
    )
    {
    }

    public function process(Task $task, $processHandler): LLMResult
    {
        $repositoryProvider = new RepositoryProvider(
            $this->getTmpTaskFolder($task),
            $this->getTaskBranch($task)
        );

        $toolsBuilder = $this->toolsFactory->createToolsBuilderWithRepository($repositoryProvider);

        if ($task->projectId) {
            $toolsBuilder->withProject($task->projectId);
            $toolsBuilder->withGit();
        }

        $tools = $toolsBuilder->build();
        $llm = $this->chatFactory->createChat($tools);
        $chat = $this->conversationFactory->fromMessages($task->messages);

        $result = $llm->process($chat, $processHandler, $task->resultRequired);

        foreach ($repositoryProvider->getProvidedRepositories() as $repo) {
            $repo->commit($result->answer);
            $repo->push();
        }

        return $result;
    }

    private function getTmpTaskFolder(Task $task): string
    {
        return uniqid('task' . $task->id . '_');
    }

    private function getTaskBranch(Task $task): string
    {
        return 'agent/task' . $task->id;
    }
}