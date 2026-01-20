<?php

namespace Anymodule\Agentmodule\Application\Actions;

use Anymodule\Agentmodule\Entity\ProcessingResult;
use Anymodule\Agentmodule\Interface\ActionContract;
use Vasenin26\Conversation\Chat;
use Vasenin26\Conversation\Interface\Conversation;
use Vasenin26\Conversation\Message;
use Vasenin26\Conversation\Messages\GitFileMessage;
use Vasenin26\Conversation\Messages\SystemMessage;
use Vasenin26\Conversation\Messages\UserMessage;
use Vasenin26\Conversation\Messages\UserTaskMessage;

/**
 * Builds a dedicated conversation for the testing agent.
 */
final class BuildTestChat implements ActionContract
{
    private const ROLE = <<<ROLE
You are **TestRunner**, an expert testing agent.

Goal: run project tests and report the result.

Rules:
- Do NOT change code.
- You MUST execute commands only via the tool `terminal-run`.
- Do NOT use shell chaining. Do NOT use `cd ... && ...`. Use the `cwd` parameter of `terminal-run`.
- Start from the base folder: /opt/repos.
- First, locate the correct repository inside /opt/repos (look for .git, composer.json, phpunit.xml, Makefile).
- Decide how to run tests by inspecting the repository (files like docker compose configs, composer scripts, phpunit config, Makefile, CI configs).
- A common pattern (if this specific repo uses it) is running PHPUnit via Docker Compose, e.g.:
  `docker compose run --rm agentmodule php vendor/bin/phpunit`
- Auto-detect how to run tests (examples: `composer test`, `vendor/bin/phpunit`, `php artisan test`, `make test`).
- If tests fail, capture the essential error details (exit code + key stderr lines).
- When finished, call the tool `result` exactly once with an object:
  { "success": true|false, "summary": "...", "errors": "..." }

ROLE;

    private const PROMPT = <<<PROMPT
Start testing now.

1) Discover the repository under /opt/repos.
2) Detect the correct test command(s) and run them.
3) Summarize the outcome and report errors if any.

Remember: only `terminal-run`, no code changes.
PROMPT;

    public function __construct(
        private TestingSession $session,
    ) {
    }

    public function execute(Conversation $conversation): \Generator
    {
        $chat = new Chat();
        $chat->addMessage(new SystemMessage(self::ROLE));

        foreach ($conversation->getMessages() as $message) {
            if ($this->shouldCopy($message)) {
                $chat->addMessage($message);
            }
        }

        $chat->addMessage(new UserMessage(self::PROMPT));

        $this->session->testChat = $chat;

        yield new ProcessingResult(
            completed: true,
            answer: 'Test chat prepared',
            conversation: new Chat(),
            context: null,
            modelName: null,
            contextFill: 0,
        );
    }

    private function shouldCopy(Message $message): bool
    {
        return $message instanceof UserMessage
            || $message instanceof UserTaskMessage
            || $message instanceof GitFileMessage
            || $message instanceof SystemMessage;
    }
}

