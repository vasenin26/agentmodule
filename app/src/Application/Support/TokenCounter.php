<?php

namespace Anymodule\Agentmodule\Application\Support;

use Anymodule\Agentmodule\Entity\ProcessingResult;

class TokenCounter
{
    private int $sent = 0;
    private int $received = 0;
    private int $total = 0;

    public function combine(ProcessingResult $result): void
    {
        $this->sent += $result->promptTokens;
        $this->received += $result->completionTokens;
        $this->total += $result->totalTokens;
    }

    public function get(): array
    {
        return [$this->sent, $this->received, $this->total];
    }
}