<?php

namespace Anymodule\Agentmodule\Services\Summary;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\ChatSummaryGeneratorInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\UserMessage;

class SummaryGenerator implements ChatSummaryGeneratorInterface
{
    const PROMPT = <<<TTT
Please analyze the current chat context and create a single, structured summary that includes:

1. Chat Topic — the main subject or purpose of the conversation.
2. Work Completed — all actions already performed by the agent, including any tool calls and their results.
3. Outstanding Tasks / Next Steps — what still needs to be done.
4. Important Notes / Observations — key findings, limitations, or warnings.

Format your response clearly so that it can later be used as context for you to continue work, understanding what has been done and what remains. Do not invent new facts; use only the information in the chat. Output everything as one message.
TTT;


    public function __construct(
        private ChatAgentFactoryInterface   $chatAgentFactory,
        private ToolServiceFactoryInterface $toolServiceFactory,
    )
    {
    }

    /**
     * @param Conversation $conversation
     * @return \Generator<ProcessingResult>
     */
    public function generate(\Vasenin26\Conversation\Interface\Conversation $conversation): \Generator
    {
        $summaryChat = new Chat();

        foreach ($conversation->getMessages() as $message) {
            $summaryChat->addMessage($message);
        }

        $summaryChat->addMessage(new UserMessage(self::PROMPT));

        $agent = $this->chatAgentFactory->createAgent($this->toolServiceFactory->createToolsBuilder()->build());
        $generator = $agent->execute($summaryChat);

        yield new ProcessingResult(
            completed: false,
            answer: 'Start generate summary',
            conversation: new Chat(),
            contextFill: 0,
        );

        foreach ($generator as $processingResult) {
            yield $processingResult;
        }
    }
}