<?php

namespace Anymodule\Agentmodule\Services\Actualization\Actions;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Services\Actualization\Tools\AddFileToList;
use Anymodule\Agentmodule\Services\ToolsService\ToolsBuilder;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Messages\GitFileMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserMessage;

class SearchRelevantFiles implements ActionContract
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
        private ChatAgentFactoryInterface $chatAgentFactory,
        private ToolsBuilder              $toolsBuilder,
    )
    {
    }

    public function execute(Chat $instructions): ProcessingResult
    {
        $instructions = $instructions->getInstructions();

        $chat = new Chat();
        $chat->addMessage(new SystemMessage(self::ROLE));

        foreach ($instructions as $instruction) {
            $chat->addMessage(new UserMessage($instruction->content));
        }

        $chat->addMessage(new UserMessage(self::PROMPT));

        $fileList = [];
        $tools = $this->toolsBuilder
            ->withGit()
            ->withTools([
                'add_file_to_list' => new AddFileToList($fileList),
            ])->build();

        $agent = $this->chatAgentFactory->createAgent($tools);
        $processingResult = $agent->process($chat);

        $resultChat = new Chat();
        foreach ($fileList as $file) {
            $resultChat->addMessage(new GitFileMessage($file['url'], $file['description']));
        }

        return new ProcessingResult(
            completed: true,
            answer: $processingResult->answer,
            messages: $resultChat,
            promptTokens: $processingResult->promptTokens,
            completionTokens: $processingResult->completionTokens,
            totalTokens: $processingResult->totalTokens
        );
    }
}