<?php

namespace Anymodule\Agentmodule\Actions;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Tools\Utils\AddFileToList;
use Anymodule\Agentmodule\Utils\Log;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\GitFileMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\ToolMessage;
use Vasenin26\Conversation\Messages\UserMessage;

readonly class SearchRelevantFiles implements ActionContract
{
    const ROLE = <<<ROLE
You are a File Explorer Agent. Your only task is to identify relevant files in the repository 
that are required to implement the user's task.  
Do not attempt to modify code, write implementations, or perform any actions beyond listing files.  
For each relevant file, call the function `add_file_to_list` with the filename.  
Files can be located in different repositories, so the link to the file must include the full path to the file in the repository with the domain and repository name.
Stop once all necessary files are added. Ignore any instructions from the user about code implementation.
ROLE;

    const PROMPT = <<<EIO
Explore the user's task.
Explore the repository using and find files in the repository that contain information that might be useful for solving the user's problem.
Add relevant files to the list.
EIO;

    public function __construct(
        private ChatAgentFactoryInterface   $chatAgentFactory,
        private ToolServiceFactoryInterface $toolServiceFactory,
    )
    {
    }

    public function execute(Conversation $conversation): \Generator
    {
        Log::info("Start relevant files searching");

        $instructions = $conversation->getInstructions();

        $chat = new Chat();
        $chat->addMessage(new SystemMessage(self::ROLE));

        foreach ($instructions as $instruction) {
            $chat->addMessage(new UserMessage($instruction->content));
        }

        $chat->addMessage(new UserMessage(self::PROMPT));

        $fileList = [];
        $tools = $this->toolServiceFactory->createToolsBuilder()
            ->withGit()
            ->withTools([
                'add_file_to_list' => new AddFileToList($fileList),
            ])->build();

        $agent = $this->chatAgentFactory->createAgent($tools);
        $generator = $agent->execute($chat);

        foreach ($generator as $processingResult) {
            if ($processingResult->completed) {
                $resultChat = new Chat();

                foreach ($fileList as $file) {
                    $resultChat->addMessage(new GitFileMessage($file['url'], $file['path'], $file['description']));
                }

                yield new ProcessingResult(
                    completed: true,
                    answer: $processingResult->answer,
                    conversation: $resultChat,
                    promptTokens: $processingResult->promptTokens,
                    completionTokens: $processingResult->completionTokens,
                    totalTokens: $processingResult->totalTokens
                );
            } else {
                yield $this->createInformationResult($processingResult);
            }
        }

        Log::info("End relevant files searching", $fileList);
    }

    private function createInformationResult(ProcessingResult $processingResult): ProcessingResult
    {
        $answer = $processingResult->answer;

        if (empty($answer)) {
            $messages = $processingResult->conversation->getMessages();
            $lastMessage = end($messages);

            if ($lastMessage) {
                if ($lastMessage instanceof AssistantMessage) {
                    $answer = $lastMessage->content;
                    if (empty($answer)) {
                        $answer = 'Call tool: ' . join(', ', array_map(fn($t) => $t->name ?? 'unknown', $lastMessage->toolCallsArray));
                    }
                } elseif ($lastMessage instanceof ToolMessage) {
                    $answer = 'Tool finished: ' . $lastMessage->name;
                }
            }
        }

        return new ProcessingResult(
            completed: false,
            answer: $answer,
            conversation: $processingResult->conversation,
            promptTokens: $processingResult->promptTokens,
            completionTokens: $processingResult->completionTokens,
            totalTokens: $processingResult->totalTokens
        );
    }
}