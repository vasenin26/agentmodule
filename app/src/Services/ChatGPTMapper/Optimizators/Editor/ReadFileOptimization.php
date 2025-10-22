<?php

namespace Anymodule\Agentmodule\Services\ChatGPTMapper\Optimizators\Editor;

use Anymodule\Agentmodule\Application\Tools\Git\ReadFile;
use Anymodule\Agentmodule\Services\ChatGPTMapper\Interface\ToolResultOptimizationInterface;
use Vasenin26\Conversation\Messages\ToolMessage;

class ReadFileOptimization implements ToolResultOptimizationInterface
{

    public function supports(ToolMessage $message): bool
    {
        return $message->name === ReadFile::NAME;
    }

    public function optimize(ToolMessage $message): ToolMessage
    {
        $result = $message->result;
        $data = json_decode($result, true);
        $payload = $data["payload"] ?? [];
        $content = $payload["content"] ?? '';

        if ($content === '') {
            return $message; // nothing to optimize
        }

        $lines = explode("\n", $content);
        $fullLength = count($lines);
        $slice = array_slice($lines, 0, 50);
        $shown = count($slice);
        $cutContent = join("\n", $slice);

        return new ToolMessage(
            $message->success,
            $message->id,
            $message->name,
            $message->args,
            json_encode([
                    'message' => 'Optimized file',
                    'payload' => [
                        'content' =>
                            "This is cut content of file."
                            . " These are the first {$shown} of {$fullLength} lines.\n"
                            . "``` $cutContent ``` \n"
                            . "read file again for getting full content"
                    ]
                ]
            )
        );
    }
}