<?php

namespace Anymodule\Agentmodule\Utils;

use Vasenin26\Conversation\Interface\MessageLinkInterface;

class HandledServiceMessageLink implements MessageLinkInterface
{
    /**
     * @var callable
     */
    private $handler;

    public function __construct(
        private readonly MessageLinkInterface $link,
        callable                              $handler
    )
    {
        $this->handler = $handler;
    }

    public function setMessage(string $message): void
    {
        $this->link->setMessage($message);
        call_user_func($this->handler);
    }

    public function setError(string $error): void
    {
        $this->link->setError($error);
        call_user_func($this->handler);
    }

    public function complete(): void
    {
        $this->link->complete();
        call_user_func($this->handler);
    }
}