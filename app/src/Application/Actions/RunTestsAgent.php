<?php

namespace Anymodule\Agentmodule\Application\Actions;

use Anymodule\Agentmodule\Application\Tools\SendResult;
use Anymodule\Agentmodule\Entity\Context;
use Anymodule\Agentmodule\Entity\ContextConversation;
use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Messages\ToolMessage;

/**
 * Runs the testing LLM agent in the prepared chat.
 */
final class RunTestsAgent implements ActionContract
{
    public function __construct(
        private TestingSession $session,
        private ChatAgentFactoryInterface $chatAgentFactory,
        private ToolServiceFactoryInterface $toolServiceFactory,
        private AgentMetaProviderInterface $agentMetaProvider,
        private GitRepoProviderInterface $gitRepoProvider,
    ) {
    }

    public function execute(Conversation $conversation): \Generator
    {
        if (!$this->session->testChat instanceof Chat) {
            $this->session->success = false;
            $this->session->errors = 'Testing chat is not prepared';

            yield new ProcessingResult(
                completed: true,
                answer: 'Testing failed: no test chat',
                conversation: new Chat(),
                context: null,
                modelName: null,
                contextFill: 0,
            );
            return;
        }

        $tools = $this->toolServiceFactory
            ->createToolsBuilder()
            ->withTerminal()
            ->withTools([new SendResult()])
            ->build();

        $modelName = $this->agentMetaProvider->getDefaultModel();
        $agent = $this->chatAgentFactory->createModelContextAgent($modelName, $tools, $this->gitRepoProvider);

        $generator = $agent->execute(new ContextConversation(Context::empty(), $this->session->testChat));

        foreach ($generator as $result) {
            if (!$result->completed) {
                yield $result;
                continue;
            }

            $this->readResultFromChat($this->session->testChat);

            // IMPORTANT: do not leak the internal test chat messages into the main conversation.
            yield new ProcessingResult(
                completed: true,
                answer: $this->session->success ? 'Tests passed' : 'Tests failed',
                conversation: new Chat(),
                context: null,
                modelName: $result->modelName,
                contextFill: $result->contextFill,
                promptTokens: $result->promptTokens,
                completionTokens: $result->completionTokens,
                totalTokens: $result->totalTokens,
                payload: [
                    'success' => $this->session->success,
                    'summary' => $this->session->summary,
                    'errors' => $this->session->errors,
                ],
            );
        }
    }

    private function readResultFromChat(Chat $chat): void
    {
        $toolMessage = $this->findLastResultToolMessage($chat);

        if (!$toolMessage) {
            $this->session->success = false;
            $this->session->errors = 'No `result` tool output found in testing chat';
            return;
        }

        $decoded = json_decode($toolMessage->result, true);
        $payload = is_array($decoded) ? ($decoded['payload'] ?? null) : null;
        $payload = is_array($payload) ? $payload : [];

        $successRaw = $payload['success'] ?? null;
        $success = filter_var($successRaw, FILTER_VALIDATE_BOOL, FILTER_NULL_ON_FAILURE);

        $this->session->success = $success ?? false;
        $this->session->summary = (string)($payload['summary'] ?? '');
        $this->session->errors = (string)($payload['errors'] ?? '');
    }

    private function findLastResultToolMessage(Chat $chat): ?ToolMessage
    {
        $found = null;

        foreach ($chat->getMessages() as $message) {
            if ($message instanceof ToolMessage && $message->name === SendResult::NAME) {
                $found = $message;
            }
        }

        return $found;
    }
}

