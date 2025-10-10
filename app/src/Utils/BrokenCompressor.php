<?php

namespace Anymodule\Agentmodule\Utils;

use Anymodule\Agentmodule\Interface\ConversationCompressorInterface;
use Vasenin26\Conversation\Interface\Conversation;

class BrokenCompressor implements ConversationCompressorInterface
{

    public function compress(Conversation $conversation): Conversation
    {
        throw new \Exception('Cant compress noop compressor');
    }
}