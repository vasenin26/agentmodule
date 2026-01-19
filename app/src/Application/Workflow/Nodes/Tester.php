<?php

namespace Anymodule\Agentmodule\Application\Workflow\Nodes;

use Anymodule\Agentmodule\Application\Actions\BuildTestChat;
use Anymodule\Agentmodule\Application\Actions\RunTestsAgent;
use Anymodule\Agentmodule\Application\Actions\TestingSession;
use Anymodule\Agentmodule\Application\Workflow\Interface\NodeProcessorInterface;
use Anymodule\Agentmodule\Application\Workflow\Interface\TesterContextInterface;
use Anymodule\Agentmodule\Interface\AgentMetaProviderInterface;
use Anymodule\Agentmodule\Interface\Factory\ActionRunnerFactoryInterface;
use Anymodule\Agentmodule\Interface\Factory\ChatAgentFactoryInterface;
use Anymodule\Agentmodule\Interface\Git\GitRepoProviderInterface;
use Anymodule\Agentmodule\Interface\Tools\ToolServiceFactoryInterface;
use Anymodule\Agentmodule\Services\Workflows\DTO\StepResult;
use Anymodule\Agentmodule\Services\Workflows\Interface\Context;
use Vasenin26\Conversation\Enum\ServiceStatus;
use Vasenin26\Conversation\Messages\AssistantMessage;
use Vasenin26\Conversation\Messages\ServiceMessage;

class Tester implements NodeProcessorInterface
{
    public function __construct(
        private ActionRunnerFactoryInterface $actionRunnerFactory,
        private ChatAgentFactoryInterface $chatAgentFactory,
        private ToolServiceFactoryInterface $toolServiceFactory,
        private AgentMetaProviderInterface $agentMetaProvider,
        private GitRepoProviderInterface $gitRepoProvider,
    ) {
    }

    private function startMessageKey(string $prefix): string
    {
        return $prefix . '.' . bin2hex(random_bytes(8));
    }

    public function process(TesterContextInterface|Context $ctx): \Generator
    {
        $startLink = $ctx->getContextConversation()->conversation->addServiceMessage(
            new ServiceMessage(
                $this->startMessageKey('node.start.tester'),
                'Tester начал работу',
                [],
                ServiceStatus::PROCESSING
            )
        );

        if (!($ctx instanceof TesterContextInterface)) {
            $startLink->complete();
            yield new StepResult(true);
            return;
        }

        $session = new TestingSession('/opt/repos');

        $build = new BuildTestChat($session);
        $run = new RunTestsAgent(
            $session,
            $this->chatAgentFactory,
            $this->toolServiceFactory,
            $this->agentMetaProvider,
            $this->gitRepoProvider,
        );

        // ActionRunner runs the last key first (array_pop), so order is reversed on purpose.
        $runner = $this->actionRunnerFactory->createForTask($ctx->getTask(), [
            'run-tests' => $run,
            'build-test-chat' => $build,
        ]);

        $runner->run($ctx->getContextConversation()->conversation);

        $success = ($session->success === true);
        $ctx->setTestResult($success);

        $ctx->getContextConversation()->conversation->addMessage(new AssistantMessage(
            $this->formatReport($success, $session->summary, $session->errors),
            []
        ));

        $startLink->complete();
        yield new StepResult(true);
    }

    private function formatReport(bool $success, string $summary, string $errors): string
    {
        $statusLine = $success ? 'PASSED' : 'FAILED';
        $text = "Test result: {$statusLine}";

        if (trim($summary) !== '') {
            $text .= "\n\nSummary:\n" . trim($summary);
        }

        if (trim($errors) !== '') {
            $text .= "\n\nErrors:\n```\n" . trim($errors) . "\n```";
        }

        return $text;
    }
}